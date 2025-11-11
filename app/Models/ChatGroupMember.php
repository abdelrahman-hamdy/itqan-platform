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
        'is_muted',
        'joined_at',
        'last_read_at',
        'unread_count',
    ];
    
    protected $casts = [
        'can_send_messages' => 'boolean',
        'is_muted' => 'boolean',
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'unread_count' => 'integer',
    ];
    
    /**
     * Get the group this membership belongs to
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }
    
    /**
     * Get the user this membership belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
     * Check if member can manage group
     */
    public function canManageGroup(): bool
    {
        return in_array($this->role, [ChatGroup::ROLE_ADMIN, ChatGroup::ROLE_MODERATOR]);
    }
    
    /**
     * Mark all messages as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'last_read_at' => now(),
            'unread_count' => 0,
        ]);
    }
    
    /**
     * Increment unread count
     */
    public function incrementUnreadCount(): void
    {
        $this->increment('unread_count');
    }
}
