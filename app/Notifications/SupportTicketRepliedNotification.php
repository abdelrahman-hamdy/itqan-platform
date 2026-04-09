<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public SupportTicket $ticket,
        public User $replier,
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
            'type' => 'support_ticket_replied',
            'title' => __('support.notifications.reply_title'),
            'message' => __('support.notifications.reply_message', [
                'name' => $this->replier->name,
            ]),
            'ticket_id' => $this->ticket->id,
        ];
    }
}
