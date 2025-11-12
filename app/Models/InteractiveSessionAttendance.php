<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interactive Session Attendance Model
 *
 * Extends BaseSessionAttendance with Interactive-specific fields:
 * - video_completion_percentage: Percentage of video content watched (0-100)
 * - quiz_completion: Whether session quizzes were completed
 * - exercises_completed: Number of practical exercises completed
 * - interaction_score: Level of interaction with course content (0-10)
 */
class InteractiveSessionAttendance extends BaseSessionAttendance
{
    /**
     * Interactive-specific fillable fields (includes base fields + Interactive-specific)
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

        // Interactive-specific fields
        'video_completion_percentage',
        'quiz_completion',
        'exercises_completed',
        'interaction_score',
    ];

    /**
     * Interactive-specific casts (merged with parent)
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

        // Interactive-specific casts
        'video_completion_percentage' => 'decimal:2',
        'quiz_completion' => 'boolean',
        'exercises_completed' => 'integer',
        'interaction_score' => 'decimal:1',
    ];

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the interactive session this attendance belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
    }

    /**
     * Get Interactive-specific fields for attendance details
     */
    protected function getSessionSpecificDetails(): array
    {
        return [
            'video_completion_percentage' => $this->video_completion_percentage,
            'quiz_completion' => $this->quiz_completion,
            'exercises_completed' => $this->exercises_completed,
            'interaction_score' => $this->interaction_score,
        ];
    }

    /**
     * Interactive sessions use 10 minutes late threshold (default)
     */
    protected function getLateThresholdMinutes(): int
    {
        return 10;
    }

    // ========================================
    // Interactive-Specific Methods
    // ========================================

    /**
     * Record video completion progress
     */
    public function recordVideoCompletion(float $percentage): bool
    {
        if ($percentage < 0 || $percentage > 100) {
            return false;
        }

        $this->update(['video_completion_percentage' => $percentage]);

        return true;
    }

    /**
     * Record quiz completion
     */
    public function recordQuizCompletion(bool $completed): bool
    {
        $this->update(['quiz_completion' => $completed]);

        return true;
    }

    /**
     * Record exercises completed
     */
    public function recordExercisesCompleted(int $count): bool
    {
        if ($count < 0) {
            return false;
        }

        $this->update(['exercises_completed' => $count]);

        return true;
    }

    /**
     * Record interaction score
     */
    public function recordInteractionScore(float $score): bool
    {
        if ($score < 0 || $score > 10) {
            return false;
        }

        $this->update(['interaction_score' => $score]);

        return true;
    }
}
