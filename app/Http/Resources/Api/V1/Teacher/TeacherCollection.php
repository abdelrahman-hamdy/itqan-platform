<?php

namespace App\Http\Resources\Api\V1\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Teacher Collection Resource
 *
 * Collection wrapper for teacher resources with metadata
 */
class TeacherCollection extends ResourceCollection
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
                'types' => $this->getTypeBreakdown(),
                'active_count' => $this->getActiveCount(),
                'average_rating' => $this->getAverageRating(),
            ],
        ];
    }

    /**
     * Get breakdown of teachers by type
     */
    protected function getTypeBreakdown(): array
    {
        return [
            'quran' => $this->collection->filter(fn ($teacher) => $teacher->resource instanceof \App\Models\QuranTeacherProfile)->count(),
            'academic' => $this->collection->filter(fn ($teacher) => $teacher->resource instanceof \App\Models\AcademicTeacherProfile)->count(),
        ];
    }

    /**
     * Get count of active teachers
     */
    protected function getActiveCount(): int
    {
        return $this->collection->where('is_active', true)->count();
    }

    /**
     * Get average rating across all teachers
     */
    protected function getAverageRating(): ?float
    {
        $ratings = $this->collection->pluck('rating')->filter();

        if ($ratings->isEmpty()) {
            return null;
        }

        return round($ratings->average(), 2);
    }
}
