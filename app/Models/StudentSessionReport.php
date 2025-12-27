<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student Session Report Model (Quran Sessions)
 *
 * Extends BaseSessionReport with Quran-specific fields:
 * - new_memorization_degree: Degree for new Quran memorization (0-10)
 * - reservation_degree: Degree for previously memorized verses (0-10)
 */
class StudentSessionReport extends BaseSessionReport
{
    /**
     * Quran-specific fillable fields (includes base fields + Quran-specific)
     */
    protected $fillable = [
        // Base fields from BaseSessionReport
        'session_id',
        'student_id',
        'teacher_id',
        'academy_id',
        'notes',
        'meeting_enter_time',
        'meeting_leave_time',
        'actual_attendance_minutes',
        'is_late',
        'late_minutes',
        'attendance_status',
        'attendance_percentage',
        'meeting_events',
        'evaluated_at',
        'is_calculated',
        'manually_evaluated',
        'override_reason',

        // Homework tracking fields (unified homework system)
        'homework_submitted_at',
        'homework_submission_id',

        // Quran-specific fields
        'new_memorization_degree',
        'reservation_degree',
    ];

    /**
     * Quran-specific casts (includes base casts + Quran-specific)
     */
    protected $casts = [
        // Base casts inherited
        'meeting_enter_time' => 'datetime',
        'meeting_leave_time' => 'datetime',
        'actual_attendance_minutes' => 'integer',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'attendance_percentage' => 'decimal:2',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_calculated' => 'boolean',
        'manually_evaluated' => 'boolean',

        // Homework tracking casts
        'homework_submitted_at' => 'datetime',

        // Quran-specific casts
        'new_memorization_degree' => 'decimal:1',
        'reservation_degree' => 'decimal:1',
    ];

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the Quran session this report belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * Get Quran-specific performance data (average of memorization and reservation)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        if ($this->new_memorization_degree === null && $this->reservation_degree === null) {
            return null;
        }

        $scores = array_filter([
            $this->new_memorization_degree,
            $this->reservation_degree,
        ], fn ($score) => $score !== null);

        return ! empty($scores) ? round(array_sum($scores) / count($scores), 1) : null;
    }

    /**
     * Quran sessions use grace period from academy settings
     * Falls back to 15 minutes if not configured
     */
    protected function getGracePeriodMinutes(): int
    {
        return $this->session?->academy?->settings?->default_late_tolerance_minutes ?? 15;
    }

    // ========================================
    // Quran-Specific Methods
    // ========================================

    /**
     * Record teacher evaluation for Quran session
     */
    public function recordTeacherEvaluation(
        ?float $newMemorizationDegree = null,
        ?float $reservationDegree = null,
        ?string $notes = null
    ): void {
        $this->update([
            'new_memorization_degree' => $newMemorizationDegree,
            'reservation_degree' => $reservationDegree,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ]);
    }

    /**
     * Get average performance degree (alias for compatibility)
     */
    public function getAveragePerformanceDegreeAttribute(): ?float
    {
        return $this->overall_performance;
    }
}
