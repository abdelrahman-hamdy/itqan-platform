<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranSessionHomework extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'created_by',
        'has_new_memorization',
        'has_review',
        'has_comprehensive_review',
        'new_memorization_pages',
        'new_memorization_surah',
        'new_memorization_from_verse',
        'new_memorization_to_verse',
        'review_pages',
        'review_surah',
        'review_from_verse',
        'review_to_verse',
        'comprehensive_review_surahs',
        'additional_instructions',
        'due_date',
        'difficulty_level',
        'is_active',
    ];

    protected $casts = [
        'has_new_memorization' => 'boolean',
        'has_review' => 'boolean',
        'has_comprehensive_review' => 'boolean',
        'new_memorization_pages' => 'decimal:2',
        'review_pages' => 'decimal:2',
        'comprehensive_review_surahs' => 'array',
        'due_date' => 'date',
        'is_active' => 'boolean',
        'new_memorization_from_verse' => 'integer',
        'new_memorization_to_verse' => 'integer',
        'review_from_verse' => 'integer',
        'review_to_verse' => 'integer',
    ];

    /**
     * Relationships
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor for total pages
     */
    public function getTotalPagesAttribute(): float
    {
        return $this->new_memorization_pages + $this->review_pages;
    }

    /**
     * Get formatted new memorization range
     */
    public function getNewMemorizationRangeAttribute(): ?string
    {
        if (! $this->new_memorization_surah) {
            return null;
        }

        $range = $this->new_memorization_surah;

        if ($this->new_memorization_from_verse && $this->new_memorization_to_verse) {
            $range .= " (الآيات {$this->new_memorization_from_verse} - {$this->new_memorization_to_verse})";
        } elseif ($this->new_memorization_from_verse) {
            $range .= " (من الآية {$this->new_memorization_from_verse})";
        }

        return $range;
    }

    /**
     * Get formatted review range
     */
    public function getReviewRangeAttribute(): ?string
    {
        if (! $this->review_surah) {
            return null;
        }

        $range = $this->review_surah;

        if ($this->review_from_verse && $this->review_to_verse) {
            $range .= " (الآيات {$this->review_from_verse} - {$this->review_to_verse})";
        } elseif ($this->review_from_verse) {
            $range .= " (من الآية {$this->review_from_verse})";
        }

        return $range;
    }

    /**
     * Get difficulty level in Arabic
     */
    public function getDifficultyLevelArabicAttribute(): string
    {
        return match ($this->difficulty_level) {
            'easy' => 'سهل',
            'medium' => 'متوسط',
            'hard' => 'صعب',
            default => 'متوسط'
        };
    }

    /**
     * Get comprehensive review surahs as formatted string
     */
    public function getComprehensiveReviewSurahsFormattedAttribute(): ?string
    {
        if (! $this->comprehensive_review_surahs || empty($this->comprehensive_review_surahs)) {
            return null;
        }

        return implode('، ', $this->comprehensive_review_surahs);
    }

    /**
     * Get total homework types count
     */
    public function getHomeworkTypesCountAttribute(): int
    {
        return collect([
            $this->has_new_memorization,
            $this->has_review,
            $this->has_comprehensive_review,
        ])->filter()->count();
    }

    /**
     * Check if homework has any content
     */
    public function getHasAnyHomeworkAttribute(): bool
    {
        return $this->has_new_memorization || $this->has_review || $this->has_comprehensive_review;
    }

    /**
     * Check if homework is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Scope for active homework
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for homework by session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
