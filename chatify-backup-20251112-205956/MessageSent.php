<?php

namespace App\Events;

use App\Models\ChMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ChMessage $message)
    {
        $this->message = $message;

        \Log::info('🔔 [MessageSent] Event constructed', [
            'message_id' => $message->id,
            'from_id' => $message->from_id,
            'to_id' => $message->to_id,
            'body_preview' => substr($message->body, 0, 50),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to both sender and receiver private channels
        $channels = [
            new PrivateChannel('chat.' . $this->message->from_id),
            new PrivateChannel('chat.' . $this->message->to_id),
        ];

        \Log::info('📺 [MessageSent] Broadcasting on channels', [
            'channels' => ['private-chat.' . $this->message->from_id, 'private-chat.' . $this->message->to_id],
            'event' => 'message.new',
            'message_id' => $this->message->id,
        ]);

        return $channels;
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
