<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interactive Session Report Model
 *
 * Extends BaseSessionReport with Interactive-specific fields:
 * - quiz_score: Score achieved on session quizzes (0-100)
 * - video_completion_percentage: Percentage of video content watched (0-100)
 * - exercises_completed: Number of practical exercises completed
 * - engagement_score: Overall engagement score (0-10)
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
        'connection_quality_score',
        'meeting_events',
        'evaluated_at',
        'is_auto_calculated',
        'manually_overridden',
        'override_reason',

        // Interactive-specific fields
        'quiz_score',
        'video_completion_percentage',
        'exercises_completed',
        'engagement_score',
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
        'connection_quality_score' => 'integer',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_auto_calculated' => 'boolean',
        'manually_overridden' => 'boolean',

        // Interactive-specific casts
        'quiz_score' => 'decimal:2',
        'video_completion_percentage' => 'decimal:2',
        'exercises_completed' => 'integer',
        'engagement_score' => 'decimal:1',
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
     * Get Interactive-specific performance data (engagement score)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        return $this->engagement_score;
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
     * Record quiz score
     */
    public function recordQuizScore(float $score): void
    {
        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException('Quiz score must be between 0 and 100');
        }

        $this->update(['quiz_score' => $score]);
    }

    /**
     * Record video completion percentage
     */
    public function recordVideoCompletion(float $percentage): void
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Video completion must be between 0 and 100');
        }

        $this->update(['video_completion_percentage' => $percentage]);
    }

    /**
     * Record exercises completed
     */
    public function recordExercisesCompleted(int $count): void
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Exercises completed cannot be negative');
        }

        $this->update(['exercises_completed' => $count]);
    }

    /**
     * Record engagement score
     */
    public function recordEngagementScore(float $score): void
    {
        if ($score < 0 || $score > 10) {
            throw new \InvalidArgumentException('Engagement score must be between 0 and 10');
        }

        $this->update([
            'engagement_score' => $score,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Calculate overall completion percentage (video + exercises + quiz)
     */
    public function getOverallCompletionPercentageAttribute(): float
    {
        $videoWeight = 0.4;
        $exercisesWeight = 0.3;
        $quizWeight = 0.3;

        $videoCompletion = $this->video_completion_percentage ?? 0;
        $quizCompletion = $this->quiz_score ?? 0;

        // Assume 100% if all exercises completed (you might want to adjust this based on total exercises)
        $exercisesCompletion = min(100, ($this->exercises_completed ?? 0) * 20); // 5 exercises = 100%

        return round(
            ($videoCompletion * $videoWeight) +
            ($exercisesCompletion * $exercisesWeight) +
            ($quizCompletion * $quizWeight),
            2
        );
    }

    /**
     * Check if session is fully completed
     */
    public function isFullyCompleted(): bool
    {
        return $this->video_completion_percentage >= 100 &&
               $this->quiz_score !== null &&
               $this->exercises_completed > 0;
    }

    // ========================================
    // Additional Scopes
    // ========================================

    public function scopeWithQuizCompleted($query)
    {
        return $query->whereNotNull('quiz_score');
    }

    public function scopeWithVideoCompleted($query)
    {
        return $query->where('video_completion_percentage', '>=', 100);
    }

    public function scopeFullyCompleted($query)
    {
        return $query->where('video_completion_percentage', '>=', 100)
                     ->whereNotNull('quiz_score')
                     ->where('exercises_completed', '>', 0);
    }
}
