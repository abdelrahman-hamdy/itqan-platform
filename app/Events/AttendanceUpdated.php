<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceUpdated implements ShouldBroadcast
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
