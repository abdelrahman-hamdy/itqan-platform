<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTypingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $userName;
    public $conversationId;
    public $groupId;
    public $isTyping;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param string $userName
     * @param int|null $conversationId
     * @param int|null $groupId
     * @param bool $isTyping
     */
    public function __construct($userId, $userName, $conversationId = null, $groupId = null, $isTyping = true)
    {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->conversationId = $conversationId;
        $this->groupId = $groupId;
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channels = [];

        if ($this->conversationId) {
            $channels[] = new PrivateChannel('conversation.' . $this->conversationId);
        }

        if ($this->groupId) {
            $channels[] = new PresenceChannel('presence-group.' . $this->groupId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'user.typing';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'is_typing' => $this->isTyping,
            'timestamp' => now()->timestamp,
        ];
    }
}