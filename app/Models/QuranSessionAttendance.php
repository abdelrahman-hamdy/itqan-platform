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
 * Note: Quality metrics (recitation_quality, tajweed_accuracy) removed - covered by homework system
 */
class QuranSessionAttendance extends BaseSessionAttendance
{
    /**
     * Quran-specific fillable fields (includes base fields + Quran-specific)
     */
    protected $fillable = [
        // Base fields from BaseSessionAttendance
        'session_id',
        'student_id',
        'attendance_status',
        'join_time',
        'leave_time',
        'auto_join_time',
        'auto_leave_time',
        'auto_duration_minutes',
        'auto_tracked',
        'manually_overridden',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'meeting_events',
        'participation_score',
        'notes',

        // Quran-specific fields
        'verses_reviewed',
        'homework_completion',
        'pages_reviewed_today',
    ];

    /**
     * Quran-specific casts (merged with parent)
     */
    protected $casts = [
        // Parent casts inherited
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
        'auto_join_time' => 'datetime',
        'auto_leave_time' => 'datetime',
        'overridden_at' => 'datetime',
        'auto_tracked' => 'boolean',
        'manually_overridden' => 'boolean',
        'meeting_events' => 'array',
        'participation_score' => 'decimal:1',
        'auto_duration_minutes' => 'integer',

        // Quran-specific casts
        'homework_completion' => 'boolean',
        'pages_reviewed_today' => 'decimal:2',
    ];

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
