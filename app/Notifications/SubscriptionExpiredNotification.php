<?php

namespace App\Notifications;

use App\Models\BaseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

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
            'title' => __('notifications.subscription_expired.title'),
            'body' => __('notifications.subscription_expired.body', [
                'name' => $this->subscription->package_name_ar ?? $this->subscription->getSubscriptionTitle(),
            ]),
            'type' => 'subscription_expired',
        ];
    }
}
