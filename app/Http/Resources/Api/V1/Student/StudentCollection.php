<?php

namespace App\Http\Resources\Api\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Student Collection Resource
 *
 * Collection wrapper for student resources with metadata
 */
class StudentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'grade_levels' => $this->getGradeLevelBreakdown(),
                'gender_breakdown' => $this->getGenderBreakdown(),
            ],
        ];
    }

    /**
     * Get breakdown of students by grade level
     */
    protected function getGradeLevelBreakdown(): array
    {
        return $this->collection->groupBy(fn ($student) => $student->gradeLevel?->name ?? 'N/A')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    /**
     * Get breakdown of students by gender
     */
    protected function getGenderBreakdown(): array
    {
        return $this->collection->groupBy(fn ($student) => $student->gender ?? 'N/A')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }
}
