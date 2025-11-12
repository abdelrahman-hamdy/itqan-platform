<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academic Session Report Model
 *
 * Extends BaseSessionReport with Academic-specific fields:
 * - student_performance_grade: Simple grade from 1-10 for overall performance
 * - homework_text: Homework assignment text/description
 * - homework_feedback: Teacher feedback on submitted homework
 */
class AcademicSessionReport extends BaseSessionReport
{
    /**
     * Academic-specific fillable fields (includes base fields + Academic-specific)
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

        // Academic-specific fields
        'student_performance_grade',
        'homework_text',
        'homework_feedback',
    ];

    /**
     * Academic-specific casts (includes base casts + Academic-specific)
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

        // Academic-specific casts
        'student_performance_grade' => 'integer', // 1-10 grade
    ];

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the academic session this report belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Get Academic-specific performance data (student performance grade)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        return $this->student_performance_grade ? (float) $this->student_performance_grade : null;
    }

    /**
     * Academic sessions use 15 minutes grace period (default)
     */
    protected function getGracePeriodMinutes(): int
    {
        return 15; // Default 15 minutes grace period for academic sessions
    }

    // ========================================
    // Academic-Specific Methods
    // ========================================

    /**
     * Check if homework has been submitted
     */
    public function hasSubmittedHomework(): bool
    {
        return ! empty($this->homework_text);
    }

    /**
     * Record homework assignment
     */
    public function recordHomeworkAssignment(string $homeworkText): void
    {
        $this->update(['homework_text' => $homeworkText]);
    }

    /**
     * Record homework feedback
     */
    public function recordHomeworkFeedback(string $feedback): void
    {
        $this->update(['homework_feedback' => $feedback]);
    }

    /**
     * Record academic performance grade
     */
    public function recordPerformanceGrade(int $grade): void
    {
        if ($grade < 1 || $grade > 10) {
            throw new \InvalidArgumentException('Performance grade must be between 1 and 10');
        }

        $this->update([
            'student_performance_grade' => $grade,
            'evaluated_at' => now(),
        ]);
    }

    // ========================================
    // Additional Scopes
    // ========================================

    public function scopeWithHomework($query)
    {
        return $query->whereNotNull('homework_text');
    }
}
