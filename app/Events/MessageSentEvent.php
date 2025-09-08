<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $senderId;

    public $receiverId;

    public $academyId;

    public $isGroupMessage;

    /**
     * Create a new event instance.
     */
    public function __construct($senderId, $receiverId, $academyId, $isGroupMessage = false)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->academyId = $academyId;
        $this->isGroupMessage = $isGroupMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Always broadcast to the receiver
        if ($this->receiverId) {
            $channels[] = new PrivateChannel("chat.{$this->receiverId}");
        }

        // Also broadcast to the sender so they see updates in their interface
        if ($this->senderId && $this->senderId != $this->receiverId) {
            $channels[] = new PrivateChannel("chat.{$this->senderId}");
        }

        // For group messages, we might want to broadcast to all group members
        // This will be handled separately in group message logic

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'academy_id' => $this->academyId,
            'is_group_message' => $this->isGroupMessage,
        ];
    }
}
