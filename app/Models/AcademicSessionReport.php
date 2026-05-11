<?php

namespace App\Models;

use App\Models\Traits\HasHomeworkEvaluation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academic Session Report Model
 *
 * Extends BaseSessionReport with Academic-specific fields. The DB column is
 * `homework_completion_degree`; this model exposes it as `homework_degree`
 * via fillable alias + accessor/mutator so the mobile API and the
 * InteractiveSessionReport sibling can use the same key.
 *
 * @property float|null $homework_degree
 */
class AcademicSessionReport extends BaseSessionReport
{
    use HasHomeworkEvaluation;

    /**
     * Academic-specific fillable fields (merged with parent in constructor).
     * `homework_degree` is the alias; the mutator below routes it to the
     * actual `homework_completion_degree` column.
     */
    protected $fillable = [
        'homework_degree',
        'homework_completion_degree',
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
     * Merge parent casts with Academic-specific casts
     *
     * IMPORTANT: Do NOT define protected $casts property - it would override parent's casts.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'homework_completion_degree' => 'decimal:1',
        ]);
    }

    /**
     * Read the homework score via the API-facing name.
     */
    public function getHomeworkDegreeAttribute(): ?float
    {
        $value = $this->attributes['homework_completion_degree'] ?? null;

        return $value === null ? null : (float) $value;
    }

    /**
     * Write the homework score via the API-facing name. Routes to the
     * actual DB column so existing readers / migrations are unaffected.
     */
    public function setHomeworkDegreeAttribute($value): void
    {
        $this->attributes['homework_completion_degree'] = $value;
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
     * Returns homework completion degree as the performance metric (0-10 scale)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        return $this->homework_completion_degree;
    }

    /** @deprecated Unused — percentage-based status. */
    protected function getGracePeriodMinutes(): int
    {
        return 0;
    }
}
