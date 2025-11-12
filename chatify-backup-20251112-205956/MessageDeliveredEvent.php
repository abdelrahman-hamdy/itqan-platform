<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeliveredEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $fromId;
    public $toId;
    public $deliveredAt;

    /**
     * Create a new event instance.
     *
     * @param int $messageId
     * @param int $fromId
     * @param int $toId
     * @param string|null $deliveredAt
     */
    public function __construct($messageId, $fromId, $toId, $deliveredAt = null)
    {
        $this->messageId = $messageId;
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->deliveredAt = $deliveredAt ?: now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('chat.' . $this->fromId),
            new PrivateChannel('chat.' . $this->toId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.delivered';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'delivered_at' => $this->deliveredAt,
        ];
    }
}