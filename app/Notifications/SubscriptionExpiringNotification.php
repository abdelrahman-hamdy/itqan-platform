<?php

namespace App\Notifications;

use App\Models\BaseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public BaseSubscription $subscription,
        public int $daysRemaining,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->subscription->package_name_ar
            ?? $this->subscription->package_name_en
            ?? __('notifications.types.subscription_expiring.title');

        $expiryDate = $this->subscription->ends_at
            ?->locale('ar')
            ->translatedFormat('d F Y');

        return (new MailMessage)
            ->subject(__('notifications.subscription_expiry_email.subject', ['days' => $this->daysRemaining]))
            ->greeting(__('notifications.subscription_expiry_email.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.subscription_expiry_email.body', [
                'subscription_name' => $name,
                'days' => $this->daysRemaining,
                'expiry_date' => $expiryDate,
            ]))
            ->line(__('notifications.subscription_expiry_email.action_hint'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'title' => __('notifications.types.subscription_expiring.title'),
            'message' => __('notifications.types.subscription_expiring.message', [
                'subscription_name' => $this->subscription->package_name_ar
                    ?? $this->subscription->package_name_en
                    ?? __('notifications.types.subscription_expiring.title'),
                'expiry_date' => $this->subscription->ends_at
                    ?->locale('ar')
                    ->translatedFormat('d F Y'),
            ]),
            'subscription_id' => $this->subscription->id,
            'subscription_type' => get_class($this->subscription),
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
