<?php

namespace App\Notifications;

use App\Models\BaseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Fired when a hybrid-cycle subscription expires while its current cycle was
 * still unpaid (P8 path). Distinct from {@see SubscriptionExpiredNotification}
 * because the operator-facing UX shows a "Pay overdue + resubscribe" CTA
 * instead of the plain "Renew" CTA — the student owes for the cycle they
 * already had access to.
 */
class SubscriptionExpiredUnpaid extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Exponential-ish backoff so transient DB blips don't burn all retries. */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public BaseSubscription $subscription,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'subscription_type' => $this->subscription->getSubscriptionType(),
            'subscription_code' => $this->subscription->subscription_code,
            'title' => __('notifications.subscription_expired_unpaid.title'),
            'body' => __('notifications.subscription_expired_unpaid.body', [
                'name' => $this->subscription->package_name_ar ?? $this->subscription->getSubscriptionTitle(),
            ]),
            'type' => 'subscription_expired_unpaid',
        ];
    }
}
