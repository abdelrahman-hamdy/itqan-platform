<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public SupportTicket $ticket,
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
            'type' => 'support_ticket_created',
            'title' => __('support.notifications.new_ticket_title'),
            'message' => __('support.notifications.new_ticket_message', [
                'name' => $this->ticket->user->name,
                'reason' => $this->ticket->reason->label(),
            ]),
            'ticket_id' => $this->ticket->id,
        ];
    }
}
