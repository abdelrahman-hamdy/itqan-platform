<?php

namespace App\Services\Subscription;

use App\Exceptions\Subscription\OverConsumptionAttempt;
use App\Models\BaseSession;
use App\Models\BaseSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\User;
use App\Services\Subscription\Concerns\RecordsSubscriptionAudit;
use App\Support\Subscriptions\SubscriptionLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionConsumption — the SOLE writer of `session_consumption` rows
 * (Phase A.2 — kills R2 in docs/subscription-recovery-plan.md).
 *
 * Implements the P5 precedence cascade from
 * docs/subscription-invariants.md §5:
 *
 *     admin_manual > teacher_report > auto_attendance
 *
 * Lower-precedence writes against an existing higher-precedence row are
 * dropped (returns null) and audit-logged as `consumption_demoted_attempt`.
 * Equal-or-higher precedence writes UPDATE in place and audit-log as
 * `consumption_promoted` (when the source changed) or `consumption_updated`.
 *
 * Every public mutator follows the lock + reconciler call shape required
 * by §6:
 *
 *     SubscriptionLock::for($sub, function () {
 *         DB::transaction(function () {
 *             // 1. mutate session_consumption row
 *             // 2. SubscriptionReconciler::sync($sub);   ← LAST line
 *         });
 *     });
 *
 * The reconciler re-derives `cycle.sessions_used` from the active rows in
 * this table (INV-B3) and validates every invariant before the transaction
 * commits. INV-B4 enforcement (no negative `sessions_remaining`) happens
 * INSIDE this service — earlier than the reconciler — so the caller gets a
 * meaningful {@see OverConsumptionAttempt} instead of an opaque invariant
 * violation. The reconciler still acts as a backstop.
 *
 * Phase E removes the legacy dual writers (`subscription_counted` on
 * sessions, `subscription_counted_at` on meeting_attendances). Until then
 * both paths run; the legacy flags become read-derived projections per §5.
 */
class SubscriptionConsumption
{
    use RecordsSubscriptionAudit;

    public function __construct(
        private readonly SubscriptionReconciler $reconciler,
    ) {}

    /**
     * Record (or promote/update) a consumption row for `$session` against
     * `$sub`'s current/anchor cycle, applying the P5 precedence cascade.
     *
     * Algorithm (per §5):
     *   1. Acquire `SubscriptionLock::for($sub)`.
     *   2. Look up existing row keyed on (session, subscription).
     *   3. If none → insert; check INV-B4 first.
     *   4. If existing.source precedence ≤ incoming precedence → UPDATE.
     *      (Promotion path: keep the row, replace source/type/timing.)
     *   5. Else → DROP write, audit-log `consumption_demoted_attempt`.
     *   6. Call SubscriptionReconciler::sync($sub) as the last call inside
     *      the locked block.
     *
     * Returns the persisted row, or `null` when the write was dropped due
     * to precedence.
     *
     * @throws OverConsumptionAttempt when accepting the row would push
     *                                `cycle.sessions_remaining` below 0.
     */
    public function record(
        BaseSession $session,
        User $student,
        BaseSubscription $sub,
        string $source,
        ?User $sourceUser,
        string $consumptionType,
    ): ?SessionConsumption {
        $this->assertKnownSource($source);
        $this->assertKnownConsumptionType($consumptionType);

        return SubscriptionLock::for($sub, function () use (
            $session,
            $student,
            $sub,
            $source,
            $sourceUser,
            $consumptionType,
        ) {
            return DB::transaction(function () use (
                $session,
                $student,
                $sub,
                $source,
                $sourceUser,
                $consumptionType,
            ) {
                return $this->withAudit($sub, 'consumption.record', $source, $sourceUser, function () use (
                    $session,
                    $student,
                    $sub,
                    $source,
                    $sourceUser,
                    $consumptionType,
                ) {
                    $cycle = $this->resolveAnchorCycle($session, $sub);

                    $sessionType = $session->getMorphClass();
                    $subscriptionType = $sub->getMorphClass();

                    $existing = SessionConsumption::query()
                        ->where('session_id', $session->getKey())
                        ->where('session_type', $sessionType)
                        ->where('subscription_id', $sub->getKey())
                        ->where('subscription_type', $subscriptionType)
                        ->lockForUpdate()
                        ->first();

                    $incomingPrecedence = SessionConsumption::precedenceFor($source);

                    if ($existing === null) {
                        // Fresh write — guard INV-B4 before inserting.
                        $this->assertCycleHasQuota($sub, $cycle);

                        $row = SessionConsumption::create([
                            'session_id' => $session->getKey(),
                            'session_type' => $sessionType,
                            'subscription_id' => $sub->getKey(),
                            'subscription_type' => $subscriptionType,
                            'cycle_id' => $cycle->getKey(),
                            'student_user_id' => $student->getKey(),
                            'consumption_type' => $consumptionType,
                            'source' => $source,
                            'source_user_id' => $sourceUser?->getKey(),
                            'consumed_at' => now(),
                        ]);

                        $this->reconciler->sync($sub);

                        return $row;
                    }

                    $existingPrecedence = SessionConsumption::precedenceFor($existing->source);

                    if ($incomingPrecedence < $existingPrecedence) {
                        // Lower-precedence attempt — drop and audit-log.
                        Log::info('consumption_demoted_attempt', [
                            'session_consumption_id' => $existing->id,
                            'session_id' => $session->getKey(),
                            'session_type' => $sessionType,
                            'subscription_id' => $sub->getKey(),
                            'subscription_type' => $subscriptionType,
                            'existing_source' => $existing->source,
                            'incoming_source' => $source,
                            'existing_consumption_type' => $existing->consumption_type,
                            'incoming_consumption_type' => $consumptionType,
                        ]);

                        return null;
                    }

                    // Equal-or-higher precedence — UPDATE in place. If the row
                    // had been reversed, this reactivates it; check INV-B4
                    // again because the active count would increase by one.
                    $isReactivating = $existing->reversed_at !== null;
                    if ($isReactivating) {
                        $this->assertCycleHasQuota($sub, $cycle);
                    }

                    $sourceChanged = $existing->source !== $source;

                    $existing->fill([
                        'cycle_id' => $cycle->getKey(),
                        'consumption_type' => $consumptionType,
                        'source' => $source,
                        'source_user_id' => $sourceUser?->getKey(),
                        'consumed_at' => now(),
                        'reversed_at' => null,
                        'reversed_reason' => null,
                        'reversed_by_user_id' => null,
                    ]);
                    $existing->save();

                    Log::info($sourceChanged ? 'consumption_promoted' : 'consumption_updated', [
                        'session_consumption_id' => $existing->id,
                        'session_id' => $session->getKey(),
                        'session_type' => $sessionType,
                        'subscription_id' => $sub->getKey(),
                        'subscription_type' => $subscriptionType,
                        'from_source' => $sourceChanged ? $existing->getOriginal('source') : null,
                        'to_source' => $source,
                        'consumption_type' => $consumptionType,
                        'reactivated' => $isReactivating,
                    ]);

                    $this->reconciler->sync($sub);

                    return $existing->fresh();
                });
            });
        });
    }

    /**
     * Reverse an active consumption row (INV-B5: atomic write of
     * reversed_at + reversed_reason + reversed_by_user_id).
     *
     * Lock + reconciler are applied the same way as `record()`. The
     * reconciler then re-derives cycle counters from the remaining active
     * rows.
     *
     * Idempotent on already-reversed rows — re-reversing simply returns
     * the existing row unchanged. (Re-running with a different reason is
     * intentionally a no-op; if you need to amend a reason, write a new
     * audit log entry.)
     *
     * Use {@see reverseLocked()} from any caller that already holds the
     * SubscriptionLock for $row->subscription — re-acquiring the same lock
     * here would deadlock (INV-C1; the cache lock has no reentrancy
     * registry, second acquire would block 5s then throw).
     */
    public function reverse(
        SessionConsumption $row,
        string $reason,
        User $reverser,
    ): SessionConsumption {
        $sub = $this->resolveSubscriptionOrFail($row);

        return SubscriptionLock::for($sub, function () use ($row, $reason, $reverser) {
            return $this->reverseLocked($row, $reason, $reverser);
        });
    }

    /**
     * Lock-free reverse body — for callers already inside
     * {@see SubscriptionLock::for}. See {@see reverse()} for the lock-
     * acquiring variant.
     *
     * Nested DB::transaction is a savepoint per Laravel; withAudit only
     * captures snapshots + writes an audit row (no lock); reconciler->sync
     * does not re-enter the lock — all safe under the existing hold.
     */
    public function reverseLocked(
        SessionConsumption $row,
        string $reason,
        User $reverser,
    ): SessionConsumption {
        $sub = $this->resolveSubscriptionOrFail($row);

        return DB::transaction(function () use ($row, $reason, $reverser, $sub) {
            return $this->withAudit($sub, 'consumption.reverse', 'admin', $reverser, function () use ($row, $reason, $reverser, $sub) {
                /** @var SessionConsumption $locked */
                $locked = SessionConsumption::query()
                    ->whereKey($row->id)
                    ->lockForUpdate()
                    ->first();

                if ($locked === null) {
                    throw new \RuntimeException("SessionConsumption#{$row->id} disappeared mid-reverse.");
                }

                if ($locked->reversed_at !== null) {
                    // Already reversed — no-op. Surface for caller's
                    // observability but don't double-write.
                    Log::info('consumption_reverse_noop_already_reversed', [
                        'session_consumption_id' => $locked->id,
                        'reversed_at' => $locked->reversed_at?->toIso8601String(),
                    ]);

                    return $locked;
                }

                $locked->fill([
                    'reversed_at' => now(),
                    'reversed_reason' => $reason,
                    'reversed_by_user_id' => $reverser->getKey(),
                ]);
                $locked->save();

                Log::info('consumption_reversed', [
                    'session_consumption_id' => $locked->id,
                    'reason' => $reason,
                    'reversed_by_user_id' => $reverser->getKey(),
                ]);

                $this->reconciler->sync($sub);

                return $locked->fresh();
            });
        });
    }

    /**
     * Count active (non-reversed) consumption rows for the cycle. This is
     * the canonical INV-B3 number — `cycle.sessions_used` MUST equal this
     * after every reconcile.
     */
    public function cycleConsumedCount(SubscriptionCycle $cycle): int
    {
        return SessionConsumption::query()
            ->where('cycle_id', $cycle->getKey())
            ->whereNull('reversed_at')
            ->count();
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Pick the cycle a session should charge against:
     *   - Honor the session's `subscription_cycle_id` stamp if present
     *     (cycle anchored on the row at scheduling time).
     *   - Otherwise fall back to the subscription's `current_cycle_id`.
     *
     * Raises if neither resolves — callers should validate before reaching
     * the writer.
     */
    private function resolveSubscriptionOrFail(SessionConsumption $row): BaseSubscription
    {
        $sub = $row->subscription;

        if (! $sub instanceof BaseSubscription) {
            throw new \RuntimeException(sprintf(
                'SessionConsumption#%d has no resolvable subscription (subscription_type=%s, subscription_id=%d).',
                $row->id,
                $row->subscription_type,
                $row->subscription_id,
            ));
        }

        return $sub;
    }

    private function resolveAnchorCycle(BaseSession $session, BaseSubscription $sub): SubscriptionCycle
    {
        $cycleId = $session->subscription_cycle_id ?? $sub->current_cycle_id;

        if ($cycleId === null) {
            throw new \RuntimeException(sprintf(
                'Cannot record consumption: session %s#%d and subscription %s#%d have no anchor cycle.',
                $session->getMorphClass(),
                $session->getKey(),
                $sub->getMorphClass(),
                $sub->getKey(),
            ));
        }

        $cycle = SubscriptionCycle::query()
            ->whereKey($cycleId)
            ->lockForUpdate()
            ->first();

        if ($cycle === null) {
            throw new \RuntimeException("Anchor cycle #{$cycleId} not found for subscription {$sub->getMorphClass()}#{$sub->getKey()}.");
        }

        return $cycle;
    }

    /**
     * INV-B4 enforcement — refuse a write that would push the cycle's
     * `sessions_remaining` below 0. The reconciler is a backstop but
     * raising here gives the caller a clean OverConsumptionAttempt with
     * the cycle-state payload attached.
     */
    private function assertCycleHasQuota(BaseSubscription $sub, SubscriptionCycle $cycle): void
    {
        $totalSessions = (int) $cycle->total_sessions;
        $activeCount = SessionConsumption::query()
            ->where('cycle_id', $cycle->getKey())
            ->whereNull('reversed_at')
            ->count();

        // The new (incoming) row would be +1 of these.
        if ($activeCount + 1 > $totalSessions) {
            throw new OverConsumptionAttempt($sub, $cycle);
        }
    }

    private function assertKnownSource(string $source): void
    {
        if (! array_key_exists($source, SessionConsumption::SOURCE_PRECEDENCE)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown SessionConsumption source "%s". Expected one of: %s.',
                $source,
                implode(', ', array_keys(SessionConsumption::SOURCE_PRECEDENCE)),
            ));
        }
    }

    private function assertKnownConsumptionType(string $type): void
    {
        $valid = [
            SessionConsumption::TYPE_ATTENDED,
            SessionConsumption::TYPE_LATE,
            SessionConsumption::TYPE_LEFT,
            SessionConsumption::TYPE_ABSENT_COUNTED,
        ];

        if (! in_array($type, $valid, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown SessionConsumption consumption_type "%s". Expected one of: %s.',
                $type,
                implode(', ', $valid),
            ));
        }
    }
}
