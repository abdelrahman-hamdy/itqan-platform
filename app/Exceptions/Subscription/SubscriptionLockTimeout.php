<?php

namespace App\Exceptions\Subscription;

use App\Exceptions\SubscriptionException;
use App\Models\BaseSubscription;
use Throwable;

/**
 * Thrown by SubscriptionLock::for when the wait timeout expires before
 * the per-subscription advisory lock could be acquired.
 *
 * Per INV-C1 every mutator must acquire SubscriptionLock::for($sub, ...).
 * If the lock is held by another writer (web request, cron, webhook),
 * the caller blocks for up to $waitTimeoutSeconds; if still unavailable
 * this exception bubbles up to the caller.
 *
 * Cron paths use SubscriptionLock::tryFor which converts this into a
 * graceful skip per INV-C3 ("cron_skipped_locked").
 */
class SubscriptionLockTimeout extends SubscriptionException
{
    public readonly string $subscriptionMorphClass;

    public readonly int $subscriptionId;

    public readonly int $waitTimeoutSeconds;

    public function __construct(
        BaseSubscription $subscription,
        int $waitTimeoutSeconds,
        ?Throwable $previous = null,
    ) {
        $this->subscriptionMorphClass = $subscription->getMorphClass();
        $this->subscriptionId = (int) $subscription->id;
        $this->waitTimeoutSeconds = $waitTimeoutSeconds;

        parent::__construct(
            message: sprintf(
                'Timed out (after %ds) waiting for subscription lock on %s#%d.',
                $waitTimeoutSeconds,
                $this->subscriptionMorphClass,
                $this->subscriptionId,
            ),
            errorCode: 'LOCK_TIMEOUT',
            context: [
                'subscription_morph_class' => $this->subscriptionMorphClass,
                'subscription_id' => $this->subscriptionId,
                'wait_timeout_seconds' => $waitTimeoutSeconds,
            ],
            previous: $previous,
        );
    }
}
