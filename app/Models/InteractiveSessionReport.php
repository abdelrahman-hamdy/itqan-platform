<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interactive Session Report Model
 *
 * Extends BaseSessionReport with Interactive-specific fields:
 * - homework_degree: Homework quality score (0-10)
 * - notes: Teacher notes (inherited from base)
 *
 * Simplified to match Academic Session Reports structure.
 */
class InteractiveSessionReport extends BaseSessionReport
{
    /**
     * Interactive-specific fillable fields (includes base fields + Interactive-specific)
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

        // Interactive-specific field (unified with Academic)
        'homework_degree',
    ];

    /**
     * Interactive-specific casts (includes base casts + Interactive-specific)
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

        // Interactive-specific cast (0-10 scale, 1 decimal)
        'homework_degree' => 'decimal:1',
    ];

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the interactive session this report belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
    }

    /**
     * Get Interactive-specific performance data
     * Returns homework_degree as the performance metric (0-10 scale)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        return $this->homework_degree;
    }

    /**
     * Interactive sessions use 10 minutes grace period (default)
     */
    protected function getGracePeriodMinutes(): int
    {
        return 10; // Default 10 minutes grace period for interactive sessions
    }

    // ========================================
    // Interactive-Specific Methods
    // ========================================

    /**
     * Record homework grade
     */
    public function recordHomeworkGrade(float $grade, ?string $notes = null): void
    {
        if ($grade < 0 || $grade > 10) {
            throw new \InvalidArgumentException('Homework grade must be between 0 and 10');
        }

        $data = [
            'homework_degree' => $grade,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        $this->update($data);
    }

    /**
     * Record teacher evaluation (unified method)
     */
    public function recordTeacherEvaluation(
        ?float $homeworkDegree = null,
        ?string $notes = null
    ): void {
        $data = array_filter([
            'homework_degree' => $homeworkDegree,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ], fn($value) => $value !== null);

        $this->update($data);
    }

    // ========================================
    // Additional Scopes
    // ========================================

    public function scopeGraded($query)
    {
        return $query->whereNotNull('homework_degree');
    }
}
