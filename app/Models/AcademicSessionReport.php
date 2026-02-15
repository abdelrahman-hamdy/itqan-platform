<?php

namespace App\Models;

use App\Models\Traits\HasHomeworkEvaluation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academic Session Report Model
 *
 * Extends BaseSessionReport with Academic-specific fields:
 * - homework_degree: Homework quality score (0-10)
 *
 * @property float|null $homework_degree
 */
class AcademicSessionReport extends BaseSessionReport
{
    use HasHomeworkEvaluation;

    /**
     * Academic-specific fillable fields (merged with parent in constructor)
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
        \Illuminate\Database\Eloquent\Model::__construct($attributes);
    }

    /**
     * Merge parent casts with Academic-specific casts
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
     * Get the academic session this report belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Get Academic-specific performance data
     * Returns homework_degree as the performance metric (0-10 scale)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        return $this->homework_degree;
    }

    /**
     * Academic sessions use grace period from academy settings
     */
    protected function getGracePeriodMinutes(): int
    {
        return $this->session?->academy?->settings?->default_late_tolerance_minutes
            ?? config('business.attendance.grace_period_minutes', 15);
    }
}
