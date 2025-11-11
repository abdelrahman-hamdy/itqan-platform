<?php

namespace App\Events;

use App\Models\ChMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ChMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to both sender and receiver private channels
        return [
            new PrivateChannel('chat.' . $this->message->from_id),
            new PrivateChannel('chat.' . $this->message->to_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.new';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'from_id' => $this->message->from_id,
            'to_id' => $this->message->to_id,
            'body' => $this->message->body,
            'attachment' => $this->message->attachment,
            'seen' => $this->message->seen,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
