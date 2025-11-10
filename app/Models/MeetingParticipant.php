<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Participant Model
 *
 * Tracks individual participant join/leave events in meetings
 */
class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'joined_at',
        'left_at',
        'duration_seconds',
        'is_host',
        'connection_quality',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'duration_seconds' => 'integer',
        'is_host' => 'boolean',
    ];

    protected $attributes = [
        'duration_seconds' => 0,
        'is_host' => false,
        'connection_quality' => 'good',
    ];

    // ========================================
    // Relationships
    // ========================================

    /**
     * Get the meeting this participant belongs to
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user who participated
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeActive($query)
    {
        return $query->whereNotNull('joined_at')
            ->whereNull('left_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('joined_at')
            ->whereNotNull('left_at');
    }

    public function scopeHosts($query)
    {
        return $query->where('is_host', true);
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Check if participant is currently in the meeting
     */
    public function isActive(): bool
    {
        return $this->joined_at && !$this->left_at;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        if ($this->duration_seconds === 0 && $this->joined_at && !$this->left_at) {
            // Currently active - calculate current duration
            return $this->joined_at->diffInMinutes(now());
        }

        return (int) round($this->duration_seconds / 60);
    }

    /**
     * Calculate and update duration
     */
    public function calculateDuration(): int
    {
        if (!$this->joined_at) {
            return 0;
        }

        $endTime = $this->left_at ?? now();
        $duration = $this->joined_at->diffInSeconds($endTime);

        $this->update(['duration_seconds' => $duration]);

        return $duration;
    }
}
