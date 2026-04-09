<?php

namespace App\Models;

use App\Enums\SupportTicketReason;
use App\Enums\SupportTicketStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Support Ticket Model
 *
 * Represents a problem report submitted by a student or teacher.
 */
class SupportTicket extends Model
{
    use ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'user_id',
        'reason',
        'description',
        'image_path',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'reason' => SupportTicketReason::class,
        'status' => SupportTicketStatus::class,
        'closed_at' => 'datetime',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', SupportTicketStatus::OPEN);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
