<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academic Session Attendance Model
 *
 * Extends BaseSessionAttendance with Academic-specific fields:
 * - lesson_understanding: Student's understanding of the lesson (0-10)
 * - homework_completion: Whether homework was completed
 * - homework_quality: Quality of homework submission (0-10)
 * - questions_asked: Number of questions asked during session
 * - concepts_mastered: Number of concepts mastered during session
 */
class AcademicSessionAttendance extends BaseSessionAttendance
{
    /**
     * Academic-specific fillable fields (includes base fields + Academic-specific)
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

        // Academic-specific fields
        'lesson_understanding',
        'homework_completion',
        'homework_quality',
        'questions_asked',
        'concepts_mastered',
    ];

    /**
     * Academic-specific casts (merged with parent)
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

        // Academic-specific casts
        'lesson_understanding' => 'decimal:1',
        'homework_quality' => 'decimal:1',
        'homework_completion' => 'boolean',
        'questions_asked' => 'integer',
        'concepts_mastered' => 'integer',
    ];

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the academic session this attendance belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Get Academic-specific fields for attendance details
     */
    protected function getSessionSpecificDetails(): array
    {
        return [
            'lesson_understanding' => $this->lesson_understanding,
            'homework_completion' => $this->homework_completion,
            'homework_quality' => $this->homework_quality,
            'questions_asked' => $this->questions_asked,
            'concepts_mastered' => $this->concepts_mastered,
        ];
    }

    /**
     * Academic sessions can configure late threshold from subscription
     * Falls back to 10 minutes if not configured
     */
    protected function getLateThresholdMinutes(): int
    {
        return $this->session->subscription->late_tolerance_minutes ?? 10;
    }

    // ========================================
    // Academic-Specific Methods
    // ========================================

    /**
     * Record lesson understanding score
     */
    public function recordLessonUnderstanding(float $score): bool
    {
        if ($score < 0 || $score > 10) {
            return false;
        }

        $this->update(['lesson_understanding' => $score]);

        return true;
    }

    /**
     * Record homework completion
     */
    public function recordHomeworkCompletion(bool $completed, ?float $quality = null): bool
    {
        $data = ['homework_completion' => $completed];

        if ($quality !== null && $quality >= 0 && $quality <= 10) {
            $data['homework_quality'] = $quality;
        }

        $this->update($data);

        return true;
    }

    /**
     * Record academic progress metrics
     */
    public function recordAcademicProgress(int $questionsAsked, int $conceptsMastered): bool
    {
        $this->update([
            'questions_asked' => $questionsAsked,
            'concepts_mastered' => $conceptsMastered,
        ]);

        return true;
    }
}
