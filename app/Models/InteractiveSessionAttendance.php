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
 *
 * Uses constructor merge pattern for fillable and getCasts() override for casts
 * to avoid duplicating ~25 lines of parent definitions.
 */
class InteractiveSessionAttendance extends BaseSessionAttendance
{
    /**
     * Interactive-specific fillable fields only
     * Base fields are merged via constructor
     */
    protected $fillable = [
        'video_completion_percentage',
        'quiz_completion',
        'exercises_completed',
        'interaction_score',
    ];

    /**
     * Constructor - merge base fillable with Interactive-specific fields
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = array_merge(static::$baseFillable, $this->fillable);
        parent::__construct($attributes);
    }

    /**
     * Get casts - merge base casts with Interactive-specific casts
     * IMPORTANT: Do NOT define protected $casts - it would override parent's casts
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'video_completion_percentage' => 'decimal:2',
            'quiz_completion' => 'boolean',
            'exercises_completed' => 'integer',
            'interaction_score' => 'decimal:1',
        ]);
    }

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
