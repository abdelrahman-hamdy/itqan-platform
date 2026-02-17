<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasHomeworkEvaluation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interactive Session Report Model
 *
 * Extends BaseSessionReport with Interactive-specific fields:
 * - homework_degree: Homework quality score (0-10)
 *
 * @property float|null $homework_degree
 */
class InteractiveSessionReport extends BaseSessionReport
{
    use HasHomeworkEvaluation;

    /**
     * Interactive-specific fillable fields (merged with parent in constructor)
     */
    protected $fillable = [
        'homework_degree',
    ];

    /**
     * Merge parent's static base fillable fields with child-specific fields FIRST,
     * then call grandparent (Model) constructor directly to avoid BaseSessionReport overwriting fillable.
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = array_merge(parent::$baseFillable, $this->fillable);
        Model::__construct($attributes);
    }

    /**
     * Merge parent casts with Interactive-specific casts
     *
     * IMPORTANT: Do NOT define protected $casts property - it would override parent's casts.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'homework_degree' => 'decimal:1',
        ]);
    }

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
     * Interactive sessions use grace period from academy settings
     * InteractiveCourseSession gets academy through course relationship
     */
    protected function getGracePeriodMinutes(): int
    {
        return $this->session?->course?->academy?->settings?->default_late_tolerance_minutes
            ?? config('business.attendance.grace_period_minutes', 15);
    }
}
