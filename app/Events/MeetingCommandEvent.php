<?php

namespace App\Events;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting meeting commands in real-time.
 *
 * Uses ShouldBroadcastNow instead of ShouldBroadcast to:
 * 1. Broadcast immediately without queue delays for real-time UX
 * 2. Avoid multi-tenancy issues where queued jobs lose tenant context
 */
class MeetingCommandEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public QuranSession $session;

    public array $commandData;

    /**
     * Create a new event instance.
     */
    public function __construct(QuranSession $session, array $commandData)
    {
        $this->session = $session;
        $this->commandData = $commandData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("meeting.{$this->session->id}"),
            new PrivateChannel("academy.{$this->session->academy_id}.meetings"),
        ];
    }

    /**
     * Get the data to broadcast.
     * Only known-safe keys from commandData are included to prevent accidental leakage
     * if extra fields are added to the payload in future.
     */
    public function broadcastWith(): array
    {
        $allowedKeys = [
            'message_id', 'type', 'command', 'session_id', 'teacher_id',
            'teacher_identity', 'timestamp', 'data', 'targets',
            'requires_acknowledgment', 'priority', 'topic',
        ];

        return [
            'session_id' => $this->session->id,
            'command_data' => array_intersect_key($this->commandData, array_flip($allowedKeys)),
            'broadcast_at' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'meeting.command';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function shouldBroadcast(): bool
    {
        return $this->session->is_live || $this->session->status === SessionStatus::ONGOING;
    }
}
