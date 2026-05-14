<?php

namespace App\Exceptions\Subscription;

use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;
use RuntimeException;

/**
 * Raised by {@see \App\Services\Subscription\SubscriptionLifecycle::renew()}
 * when the caller asks to renew a subscription whose currentCycle still has
 * `payment_status == PENDING`.
 *
 * Decision Table 3.1 (`docs/subscription-invariants.md §3.1`):
 *   - When the subscription's view-state is `active_payment_due` (the hybrid
 *     "lie" / pending-current-cycle shape) the correct response to a Renew
 *     click is to redirect the user to the existing payment route so they
 *     pay the outstanding cycle first. Stacking a fresh cycle behind an
 *     unpaid one re-creates the cancelled-duplicate bug we just sealed.
 *
 * The HTTP layer catches this and issues the redirect; CLI / cron callers
 * should treat it as a hard error and bail out for the subscription.
 */
class RenewBlockedByPendingPayment extends RuntimeException
{
    public function __construct(
        public readonly BaseSubscription $subscription,
        public readonly ?SubscriptionCycle $pendingCycle = null,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? sprintf(
                'Renew blocked: subscription %s#%d has an unpaid current cycle (#%s) — route the click to the existing payment flow instead.',
                $subscription->getMorphClass(),
                $subscription->getKey(),
                $pendingCycle?->getKey() ?? '?',
            ),
        );
    }

    /**
     * The subscription cycle that still owes payment. May be null if the
     * caller has no cycle handle (defensive — the lifecycle service always
     * passes one in practice).
     */
    public function pendingCycle(): ?SubscriptionCycle
    {
        return $this->pendingCycle;
    }
}
