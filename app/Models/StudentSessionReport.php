<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
     * Quran-specific fillable fields (merged with parent in constructor)
     */
    protected $fillable = [
        'new_memorization_degree',
        'reservation_degree',
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
     * Merge parent casts with Quran-specific casts
     *
     * IMPORTANT: Do NOT define protected $casts property - it would override parent's casts.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'new_memorization_degree' => 'decimal:1',
            'reservation_degree' => 'decimal:1',
        ]);
    }

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
     */
    protected function getGracePeriodMinutes(): int
    {
        return $this->session?->academy?->settings?->default_late_tolerance_minutes
            ?? config('business.attendance.grace_period_minutes', 15);
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
        $data = [
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ];

        if ($newMemorizationDegree !== null) {
            $data['new_memorization_degree'] = $newMemorizationDegree;
        }

        if ($reservationDegree !== null) {
            $data['reservation_degree'] = $reservationDegree;
        }

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        $this->update($data);
    }

    /**
     * Get average performance degree (alias for compatibility)
     */
    public function getAveragePerformanceDegreeAttribute(): ?float
    {
        return $this->overall_performance;
    }
}
