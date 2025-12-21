<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quran Session Attendance Model
 *
 * Extends BaseSessionAttendance with Quran-specific fields:
 * - verses_reviewed: Number of verses reviewed
 * - homework_completion: Whether homework was completed
 * - pages_reviewed_today: Pages reviewed in this session
 *
 * Uses constructor merge pattern for fillable and getCasts() override for casts
 * to avoid duplicating ~25 lines of parent definitions.
 */
class QuranSessionAttendance extends BaseSessionAttendance
{
    /**
     * Quran-specific fillable fields only
     * Base fields are merged via constructor
     */
    protected $fillable = [
        'verses_reviewed',
        'homework_completion',
        'pages_reviewed_today',
    ];

    /**
     * Constructor - merge base fillable with Quran-specific fields
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = array_merge(static::$baseFillable, $this->fillable);
        parent::__construct($attributes);
    }

    /**
     * Get casts - merge base casts with Quran-specific casts
     * IMPORTANT: Do NOT define protected $casts - it would override parent's casts
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'homework_completion' => 'boolean',
            'pages_reviewed_today' => 'decimal:2',
        ]);
    }

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the Quran session this attendance belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * Get Quran-specific fields for attendance details
     */
    protected function getSessionSpecificDetails(): array
    {
        return [
            'homework_completion' => $this->homework_completion,
            'pages_reviewed_today' => $this->pages_reviewed_today,
        ];
    }

    /**
     * Quran sessions use 15 minutes late threshold
     */
    protected function getLateThresholdMinutes(): int
    {
        return 15;
    }

    // ========================================
    // Quran-Specific Methods
    // ========================================

    /**
     * تسجيل إكمال الواجب
     */
    public function recordHomeworkCompletion(bool $completed): bool
    {
        $this->update(['homework_completion' => $completed]);

        return true;
    }

    /**
     * تسجيل الصفحات المراجعة اليوم
     */
    public function recordReviewedPages(float $reviewedPages = 0): bool
    {
        $this->update([
            'pages_reviewed_today' => $reviewedPages,
        ]);

        return true;
    }
}
