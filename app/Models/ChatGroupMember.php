<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'can_send_messages',
        'joined_at',
        'last_read_at',
    ];

    protected $casts = [
        'can_send_messages' => 'boolean',
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
    ];

    /**
     * Get the chat group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === ChatGroup::ROLE_ADMIN;
    }

    /**
     * Check if member is moderator
     */
    public function isModerator(): bool
    {
        return $this->role === ChatGroup::ROLE_MODERATOR;
    }

    /**
     * Check if member can moderate (admin or moderator)
     */
    public function canModerate(): bool
    {
        return in_array($this->role, [ChatGroup::ROLE_ADMIN, ChatGroup::ROLE_MODERATOR]);
    }
}
