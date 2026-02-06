<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting notifications in real-time.
 *
 * Uses ShouldBroadcastNow instead of ShouldBroadcast to:
 * 1. Broadcast immediately without queue delays for real-time UX
 * 2. Avoid multi-tenancy issues where queued jobs lose tenant context
 */
class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public array $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => $this->notification,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
