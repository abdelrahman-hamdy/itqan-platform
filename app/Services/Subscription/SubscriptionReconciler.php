<?php

namespace App\Services\Subscription;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\Subscription\SubscriptionInvariantViolation;
use App\Models\BaseSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Throwable;

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

        if ($cycle instanceof SubscriptionCycle && ! $this->isLegacyConsumptionCycle($cycle)) {
            // INV-B3: cycle.sessions_used is itself a derived field — it must
            // equal the COUNT of active SessionConsumption rows anchored to
            // that cycle. Recompute that count here, BEFORE we mirror the
            // cycle's counter onto the subscription row, so any drift between
            // the cycle's stored aggregate and the consumption-row truth is
            // healed by the reconciler instead of perpetuated.
            //
            // E1 GATE: pre-v2-flip cycles never had session_consumption rows
            // written for them (legacy code only updated cycle.sessions_used
            // directly). Recounting from the empty consumption table would
            // zero out the legitimate legacy aggregate. Skipped via
            // isLegacyConsumptionCycle() until per-cycle backfill flips
            // v2_consumption_complete=true.
            $activeConsumption = SessionConsumption::query()
                ->where('cycle_id', $cycle->getKey())
                ->whereNull('reversed_at')
                ->count();

            if ((int) $cycle->sessions_used !== $activeConsumption) {
                $cycle->sessions_used = $activeConsumption;
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
     * E1 gate — true if the cycle predates the v2 flip cutoff AND has not yet
     * had its consumption rows backfilled. The reconciler MUST NOT recount
     * sessions_used from session_consumption for these cycles, because the
     * legacy attendance writer never wrote consumption rows: a recount would
     * zero out the legitimate stored aggregate.
     *
     * The cutoff is operator-driven via SUBSCRIPTIONS_V2_FLIP_CUTOFF (ISO
     * timestamp). Missing cutoff → never gate (developer / test contexts
     * where every cycle is post-v2 by construction).
     *
     * The per-cycle `v2_consumption_complete` column is the long-term shape;
     * a backfill job will flip it true once a cycle's legacy attendance has
     * been replayed into session_consumption.
     */
    private function isLegacyConsumptionCycle(SubscriptionCycle $cycle): bool
    {
        if ($cycle->v2_consumption_complete) {
            return false;
        }

        $cutoffRaw = config('subscriptions.v2_flip_cutoff');
        if (! is_string($cutoffRaw) || $cutoffRaw === '') {
            return false;
        }

        try {
            $cutoff = Carbon::parse($cutoffRaw);
        } catch (Throwable) {
            return false;
        }

        $createdAt = $cycle->created_at;
        if (! $createdAt instanceof CarbonInterface) {
            return false;
        }

        return $createdAt->lt($cutoff);
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
