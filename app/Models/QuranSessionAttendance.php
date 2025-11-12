<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quran Session Attendance Model
 *
 * Extends BaseSessionAttendance with Quran-specific fields:
 * - recitation_quality: Quality of Quran recitation (0-10)
 * - tajweed_accuracy: Accuracy of Tajweed rules (0-10)
 * - verses_reviewed: Number of verses reviewed
 * - homework_completion: Whether homework was completed
 * - pages_memorized_today: Pages memorized in this session
 * - verses_memorized_today: Verses memorized in this session
 * - pages_reviewed_today: Pages reviewed in this session
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
        'connection_quality_score',
        'participation_score',
        'notes',

        // Quran-specific fields
        'recitation_quality',
        'tajweed_accuracy',
        'verses_reviewed',
        'homework_completion',
        'papers_memorized_today',
        'verses_memorized_today',
        'pages_memorized_today',
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
        'connection_quality_score' => 'integer',
        'auto_duration_minutes' => 'integer',

        // Quran-specific casts
        'recitation_quality' => 'decimal:1',
        'tajweed_accuracy' => 'decimal:1',
        'homework_completion' => 'boolean',
        'papers_memorized_today' => 'decimal:2',
        'verses_memorized_today' => 'integer',
        'pages_memorized_today' => 'decimal:2',
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
            'recitation_quality' => $this->recitation_quality,
            'tajweed_accuracy' => $this->tajweed_accuracy,
            'homework_completion' => $this->homework_completion,
            'pages_memorized_today' => $this->pages_memorized_today,
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
     * تسجيل تقييم التلاوة
     */
    public function recordRecitationQuality(float $quality): bool
    {
        if ($quality < 0 || $quality > 10) {
            return false;
        }

        $this->update(['recitation_quality' => $quality]);

        return true;
    }

    /**
     * تسجيل دقة التجويد
     */
    public function recordTajweedAccuracy(float $accuracy): bool
    {
        if ($accuracy < 0 || $accuracy > 10) {
            return false;
        }

        $this->update(['tajweed_accuracy' => $accuracy]);

        return true;
    }

    /**
     * تسجيل إكمال الواجب
     */
    public function recordHomeworkCompletion(bool $completed): bool
    {
        $this->update(['homework_completion' => $completed]);

        return true;
    }

    /**
     * تسجيل الصفحات المحفوظة والمراجعة اليوم
     */
    public function recordPagesProgress(float $memorizedPages = 0, float $reviewedPages = 0): bool
    {
        $this->update([
            'pages_memorized_today' => $memorizedPages,
            'pages_reviewed_today' => $reviewedPages,
        ]);

        return true;
    }
}
