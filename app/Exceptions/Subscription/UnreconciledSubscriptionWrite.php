<?php

namespace App\Exceptions\Subscription;

use App\Exceptions\SubscriptionException;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Thrown by the SubscriptionRowGuard observer when a writer attempts to
 * mutate a subscription's derived columns (payment_status, sessions_used,
 * sessions_remaining, total_sessions, starts_at, ends_at) outside of
 * SubscriptionReconciler::sync.
 *
 * Per INV-A1 those fields are a mirror of currentCycle and must be
 * written only by the reconciler. Phase A keeps this exception in
 * report-only mode (controlled by config('subscriptions.row_guard_enforce'));
 * Phase B flips enforcement on once every legacy writer has been migrated.
 */
class UnreconciledSubscriptionWrite extends SubscriptionException
{
    public readonly string $subscriptionMorphClass;

    public readonly mixed $subscriptionId;

    /**
     * @param  array<int, string>  $dirtyFields
     */
    public function __construct(
        Model $subscription,
        public readonly array $dirtyFields,
        ?Throwable $previous = null,
    ) {
        $this->subscriptionMorphClass = $subscription->getMorphClass();
        $this->subscriptionId = $subscription->getKey();

        parent::__construct(
            sprintf(
                'Direct write to derived subscription field(s) [%s] on %s#%s is forbidden — '
                .'route the mutation through SubscriptionReconciler::sync (INV-A1).',
                implode(', ', $dirtyFields),
                $this->subscriptionMorphClass,
                (string) ($this->subscriptionId ?? 'new'),
            ),
            0,
            $previous,
        );
    }

    /**
     * @return array<int, string>
     */
    public function dirtyFields(): array
    {
        return $this->dirtyFields;
    }
}
