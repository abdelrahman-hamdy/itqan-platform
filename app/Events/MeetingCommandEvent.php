<?php

namespace App\Events;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingCommandEvent implements ShouldBroadcast
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
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'command_data' => $this->commandData,
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
