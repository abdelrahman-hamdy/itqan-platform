<?php

namespace App\Models\Traits;

/**
 * Trait HasHomeworkEvaluation
 *
 * Provides shared homework evaluation methods for Academic and Interactive session reports.
 * Eliminates duplication between AcademicSessionReport and InteractiveSessionReport.
 *
 * @property float|null $homework_degree
 * @property \Carbon\Carbon|null $evaluated_at
 * @property bool $manually_evaluated
 * @property string|null $notes
 */
trait HasHomeworkEvaluation
{
    /**
     * Record homework grade (0-10 scale)
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
     *
     * Uses strict null check to allow 0.0 as a valid grade value.
     */
    public function recordTeacherEvaluation(
        ?float $homeworkDegree = null,
        ?string $notes = null
    ): void {
        $data = [
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ];

        if ($homeworkDegree !== null) {
            $data['homework_degree'] = $homeworkDegree;
        }

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        $this->update($data);
    }

    /**
     * Scope to only graded reports (with homework_degree)
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('homework_degree');
    }
}
