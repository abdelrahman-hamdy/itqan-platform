<?php

namespace App\Services\Subscription;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\Subscription\SubscriptionInvariantViolation;
use App\Models\BaseSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;

/**
 * SubscriptionReconciler — the SOLE writer of derived subscription-row
 * fields (per INV-A1). Mirrors the active cycle's truth onto the
 * subscription row and then runs SubscriptionInvariantChecker.
 *
 * Call shape (per docs/subscription-invariants.md §6):
 *
 *     SubscriptionLock::for($sub, function () use ($sub) {
 *         DB::transaction(function () use ($sub) {
 *             // 1. mutate cycle rows
 *             // 2. mutate session_consumption rows
 *             // 3. queue jobs
 *             // 4. app(SubscriptionReconciler::class)->sync($sub);  ← LAST line
 *         });
 *     });
 *
 * Throws SubscriptionInvariantViolation on a failed post-mirror check;
 * the surrounding DB::transaction rolls back so the write never lands.
 *
 * The reconciler tags the model with a transient `$reconciling = true`
 * flag during its save() so SubscriptionRowGuard knows to allow the
 * derived-field writes (every other writer is denied by the guard).
 */
class SubscriptionReconciler
{
    public function __construct(
        private readonly SubscriptionInvariantChecker $checker,
    ) {}

    /**
     * Re-derive subscription-row fields from currentCycle, then validate.
     *
     * Per INV-A1: subscription.payment_status / sessions_used /
     * sessions_remaining / total_sessions / starts_at / ends_at MUST
     * equal the corresponding fields on currentCycle.
     *
     * If currentCycle is null the derived fields are left untouched;
     * status is still re-evaluated against lifecycle state (e.g.
     * PENDING → ACTIVE once a cycle exists and is paid).
     *
     * @throws SubscriptionInvariantViolation
     */
    public function sync(BaseSubscription $sub): void
    {
        $this->mirrorFromCycle($sub);

        $violations = $this->checker->check($sub);
        $blocking = array_values(array_filter(
            $violations,
            // info-severity violations are documented expected states (cleanup
            // metadata gaps, legacy-cycle markers) and must NOT block a
            // mutation. Only error/warning blocks.
            fn (array $v) => ($v['severity'] ?? 'error') !== 'info',
        ));
        if (! empty($blocking)) {
            throw new SubscriptionInvariantViolation($sub, $blocking);
        }
    }

    /**
     * Same mirroring as sync(), but skip the invariant check.
     *
     * Used by:
     *   - the bootstrap importer (`subscriptions:bootstrap-consumption-rows`),
     *     which intentionally rebuilds derived fields from cycle truth
     *     and runs the checker once at the end of the batch instead of
     *     per-row;
     *   - SubscriptionInvariantChecker itself, if it ever needs to
     *     re-mirror to test a violation hypothesis without recursing.
     */
    public function syncWithoutInvariantCheck(BaseSubscription $sub): void
    {
        $this->mirrorFromCycle($sub);
    }

    /**
     * Core mirror logic. Sets the internal $reconciling flag so the
     * SubscriptionRowGuard observer permits the derived-field write,
     * then clears it once the save completes (even on failure).
     */
    private function mirrorFromCycle(BaseSubscription $sub): void
    {
        $cycle = $sub->relationLoaded('currentCycle')
            ? $sub->currentCycle
            : $sub->currentCycle()->first();

        if ($cycle instanceof SubscriptionCycle) {
            // INV-B3: cycle.sessions_used is itself a derived field — it must
            // equal the COUNT of active SessionConsumption rows anchored to
            // that cycle, PLUS any documented offset for sessions consumed
            // outside the v2 path (admin-preset pre-platform usage, or the
            // cleanup's --allow-aggregate-shortfall metadata).
            //
            // CONSUMPTION OFFSET (post-cleanup): two metadata keys describe
            // sessions that count toward sessions_used but have NO matching
            // session_consumption row:
            //   - `unaccounted_sessions_used` (int, written by cleanup
            //     Pattern A --allow-aggregate-shortfall): legacy-aggregate
            //     drift that we accepted at cleanup time.
            //   - `pre_platform_consumption_preserved` (bool) + `preserved_value`
            //     (int, written by Pattern C): admin-preset pre-platform usage
            //     where no session rows ever existed.
            // The reconciler must add this offset back when recomputing
            // sessions_used; otherwise the admin-preset values get silently
            // wiped on the next mutator that triggers sync (sub-556 incident).
            $activeConsumption = SessionConsumption::query()
                ->where('cycle_id', $cycle->getKey())
                ->whereNull('reversed_at')
                ->count();

            $offset = $this->consumptionOffset($cycle);
            $expected = $activeConsumption + $offset;

            if ((int) $cycle->sessions_used !== $expected) {
                $cycle->sessions_used = $expected;
                $cycle->save();
            }
        }

        $sub->reconciling = true;

        try {
            if ($cycle instanceof SubscriptionCycle) {
                $sub->payment_status = $this->mapCyclePaymentStatus($cycle->payment_status);
                $sub->sessions_used = (int) $cycle->sessions_used;
                $sub->total_sessions = (int) $cycle->total_sessions;
                $sub->sessions_remaining = max(0, (int) $cycle->total_sessions - (int) $cycle->sessions_used);
                $sub->starts_at = $cycle->starts_at;
                $sub->ends_at = $cycle->ends_at;
            }

            $sub->status = $this->deriveLifecycleStatus($sub, $cycle);

            // No-op suppression: if the mirror produced an identical row we
            // skip the UPDATE — the observer's updated() hook walks audit +
            // notification logic that's pointless on a no-change tick.
            if ($sub->isDirty()) {
                $sub->save();
            }
        } finally {
            $sub->reconciling = false;
        }
    }

    /**
     * Per-cycle consumption offset — sessions that count toward
     * cycle.sessions_used but have no matching session_consumption row.
     *
     * Sources:
     *   - `metadata.unaccounted_sessions_used` (Pattern A shortfall path —
     *     legacy aggregate drift we accepted at cleanup time)
     *   - `metadata.pre_platform_consumption_preserved=true` + `preserved_value`
     *     (Pattern C — admin-preset pre-platform consumption with no
     *     session rows backing it)
     *
     * Defaults to 0 (no offset; pure recount from consumption rows).
     */
    private function consumptionOffset(SubscriptionCycle $cycle): int
    {
        $metadata = $cycle->metadata ?? [];
        if (! is_array($metadata)) {
            return 0;
        }

        $offset = 0;

        if (isset($metadata['unaccounted_sessions_used'])) {
            $offset += (int) $metadata['unaccounted_sessions_used'];
        }

        if (! empty($metadata['pre_platform_consumption_preserved'])
            && isset($metadata['preserved_value'])) {
            $offset += (int) $metadata['preserved_value'];
        }

        return max(0, $offset);
    }

    /**
     * Map a cycle's payment_status string ('pending'|'paid'|'failed') to
     * the subscription-row's SubscriptionPaymentStatus enum.
     */
    private function mapCyclePaymentStatus(?string $cyclePaymentStatus): SubscriptionPaymentStatus
    {
        return match ($cyclePaymentStatus) {
            SubscriptionCycle::PAYMENT_PAID => SubscriptionPaymentStatus::PAID,
            SubscriptionCycle::PAYMENT_FAILED => SubscriptionPaymentStatus::FAILED,
            default => SubscriptionPaymentStatus::PENDING,
        };
    }

    /**
     * Derive subscription.status from lifecycle signals (no cycle access,
     * no derived field reads — those are mirrored above).
     *
     * The Phase A.3 reconciler is conservative: terminal/explicit states
     * (CANCELLED, PAUSED) are preserved verbatim; otherwise the existing
     * status is kept so this method doesn't accidentally promote a row
     * outside the explicit lifecycle service's purview. Phase A.4 may
     * tighten this once SubscriptionLifecycle owns every transition.
     */
    private function deriveLifecycleStatus(
        BaseSubscription $sub,
        ?SubscriptionCycle $cycle,
    ): SessionSubscriptionStatus {
        $current = $sub->status instanceof SessionSubscriptionStatus
            ? $sub->status
            : SessionSubscriptionStatus::tryFrom((string) $sub->status)
                ?? SessionSubscriptionStatus::PENDING;

        // Terminal / admin-set states are sticky; reconciler never
        // walks back from them.
        if (in_array($current, [
            SessionSubscriptionStatus::CANCELLED,
            SessionSubscriptionStatus::PAUSED,
        ], true)) {
            return $current;
        }

        // No cycle ever materialised yet → PENDING. (Pre-activation.)
        if ($cycle === null) {
            return SessionSubscriptionStatus::PENDING;
        }

        return $current;
    }
}
