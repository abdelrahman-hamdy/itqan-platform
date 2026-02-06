<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting attendance updates in real-time.
 *
 * Uses ShouldBroadcastNow instead of ShouldBroadcast to:
 * 1. Broadcast immediately without queue delays for real-time UX
 * 2. Avoid multi-tenancy issues where queued jobs lose tenant context
 */
class AttendanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $sessionId;

    public int $userId;

    public array $attendanceData;

    /**
     * Create a new event instance.
     */
    public function __construct(int $sessionId, int $userId, array $attendanceData)
    {
        $this->sessionId = $sessionId;
        $this->userId = $userId;
        $this->attendanceData = $attendanceData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('session.'.$this->sessionId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'attendance' => $this->attendanceData,
            'timestamp' => now()->toISOString(),
        ];
    }
}
