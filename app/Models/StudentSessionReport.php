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
        'connection_quality_score',
        'meeting_events',
        'evaluated_at',
        'is_auto_calculated',
        'manually_overridden',
        'override_reason',

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
        'connection_quality_score' => 'integer',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_auto_calculated' => 'boolean',
        'manually_overridden' => 'boolean',

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
     * Quran sessions use configurable grace period from circle settings
     * Falls back to 15 minutes if not configured
     */
    protected function getGracePeriodMinutes(): int
    {
        $session = $this->session;
        if (! $session) {
            return 15;
        }

        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;

        return $circle?->late_join_grace_period_minutes ?? 15;
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
            'manually_overridden' => true,
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
