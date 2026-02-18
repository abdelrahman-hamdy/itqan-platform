<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wirechat\Wirechat\Models\Message;

/**
 * Message Reaction Model
 *
 * Represents emoji reactions to chat messages.
 * Multiple users can react with different emojis to the same message.
 */
class MessageReaction extends Model
{
    protected $fillable = [
        'message_id',
        'reacted_by_id',
        'reacted_by_type',
        'emoji',
    ];

    /**
     * Get the message that owns the reaction
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who reacted (polymorphic)
     */
    public function reactedBy(): MorphTo
    {
        return $this->morphTo('reacted_by');
    }
}
