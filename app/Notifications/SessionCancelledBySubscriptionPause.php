<?php

namespace App\Notifications;

use App\Models\BaseSession;
use App\Models\BaseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Fired when SubscriptionLifecycle::pause / cancel suspends or cancels a
 * future scheduled session — the student/teacher needs to know their
 * upcoming lesson was called off and why.
 *
 * Per the safe-cancellation policy (G7.a): only sessions in SCHEDULED /
 * READY status are touched. ONGOING meetings are left alone — students
 * mid-lesson aren't kicked. The MEMORY note
 * `feedback_meeting_auto_complete_must_check_participants` is the rule
 * being honoured.
 */
class SessionCancelledBySubscriptionPause extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Exponential-ish backoff so transient DB blips don't burn all retries. */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public BaseSession $session,
        public BaseSubscription $subscription,
        public string $cause,
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
            'session_id' => $this->session->id,
            'session_type' => $this->session->getMorphClass(),
            'subscription_id' => $this->subscription->id,
            'subscription_type' => $this->subscription->getMorphClass(),
            'subscription_code' => $this->subscription->subscription_code,
            'cause' => $this->cause,
            'title' => __('notifications.session_cancelled_by_subscription_pause.title'),
            'body' => __('notifications.session_cancelled_by_subscription_pause.body', [
                'scheduled_at' => $this->session->scheduled_at?->format('Y-m-d H:i'),
            ]),
            'type' => 'session_cancelled_by_subscription_pause',
        ];
    }
}
