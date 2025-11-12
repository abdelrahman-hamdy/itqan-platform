<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcastNow
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

        \Log::info('🔔 [MessageSentEvent] Event constructed', [
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'academy_id' => $academyId,
            'is_group' => $isGroupMessage,
        ]);
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

        $channelNames = array_map(fn($ch) => $ch->name, $channels);
        \Log::info('📺 [MessageSentEvent] Broadcasting on channels', [
            'channels' => $channelNames,
            'event' => 'message.sent'
        ]);

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
