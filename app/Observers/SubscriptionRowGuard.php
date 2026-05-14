<?php

namespace App\Observers;

use App\Exceptions\Subscription\UnreconciledSubscriptionWrite;
use App\Models\BaseSubscription;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionRowGuard — enforces INV-A1 at the model layer.
 *
 * The derived fields below are written ONLY by
 * SubscriptionReconciler::mirrorFromCycle, which tags the model with
 * `$model->reconciling = true` for the duration of its save(). Any other
 * writer that dirties one of these columns is breaking the invariant.
 *
 * Phase A keeps the guard in REPORT-ONLY mode by default
 * (config('subscriptions.row_guard_enforce') === false). Violations are
 * logged at warning level but the save proceeds, so legacy code can
 * keep running while the migration to SubscriptionReconciler ships
 * piecemeal. Once Phase B's test matrix proves every writer is on the
 * new path, enforcement flips on and the same violations start
 * throwing UnreconciledSubscriptionWrite.
 */
class SubscriptionRowGuard
{
    /**
     * Subscription columns whose value MUST be derived from currentCycle.
     *
     * Order matches docs/subscription-invariants.md §2 INV-A1.
     */
    private const DERIVED_FIELDS = [
        'payment_status',
        'sessions_used',
        'sessions_remaining',
        'total_sessions',
        'starts_at',
        'ends_at',
    ];

    /**
     * Eloquent `saving` hook. Fires before INSERT and UPDATE.
     *
     * Skips the check when the model is mid-reconciliation
     * ($model->reconciling === true) — that's the one writer allowed.
     */
    public function saving(BaseSubscription $sub): void
    {
        if ($sub->reconciling === true) {
            return;
        }

        $dirtyDerived = array_values(array_filter(
            self::DERIVED_FIELDS,
            fn (string $field): bool => $sub->isDirty($field),
        ));

        if ($dirtyDerived === []) {
            return;
        }

        if (config('subscriptions.row_guard_enforce', false) === true) {
            throw new UnreconciledSubscriptionWrite($sub, $dirtyDerived);
        }

        // Report-only mode: log + let the write through. The Phase A
        // migration window relies on these warnings to surface remaining
        // legacy writers before enforcement flips on.
        Log::warning('subscription.row_guard.unreconciled_write', [
            'subscription_type' => $sub->getMorphClass(),
            'subscription_id' => $sub->getKey(),
            'dirty_fields' => $dirtyDerived,
            'enforced' => false,
        ]);
    }
}
