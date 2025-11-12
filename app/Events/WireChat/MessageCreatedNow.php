<?php

namespace App\Events\WireChat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Namu\WireChat\Models\Message;

/**
 * Immediate broadcast version of WireChat's MessageCreated event
 *
 * This event broadcasts immediately (ShouldBroadcastNow) instead of being queued,
 * which is necessary for multi-tenancy support.
 */
class MessageCreatedNow implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load([]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sendable_id' => $this->message->sendable_id,
                'sendable_type' => $this->message->sendable_type,
                'body' => $this->message->body,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
        ];
    }

    /**
     * The event's broadcast name.
     *
     * We use the full namespace so WireChat's Livewire components can listen to it.
     * WireChat listens to: 'echo-private:conversation.X,.Namu\\WireChat\\Events\\MessageCreated'
     */
    public function broadcastAs(): string
    {
        return '.Namu\\WireChat\\Events\\MessageCreated';
    }
}
