<?php

namespace App\Http\Resources\Api\V1\Circle;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Circle Collection Resource
 *
 * Collection wrapper for circle resources with metadata
 */
class CircleCollection extends ResourceCollection
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
                'total_capacity' => $this->getTotalCapacity(),
                'total_students' => $this->getTotalStudents(),
            ],
        ];
    }

    /**
     * Get breakdown of circles by type
     */
    protected function getTypeBreakdown(): array
    {
        return [
            'individual' => $this->collection->filter(fn ($circle) => $circle->resource instanceof \App\Models\QuranIndividualCircle)->count(),
            'group' => $this->collection->filter(fn ($circle) => $circle->resource instanceof \App\Models\QuranCircle)->count(),
        ];
    }

    /**
     * Get count of active circles
     */
    protected function getActiveCount(): int
    {
        return $this->collection->where('is_active', true)->count();
    }

    /**
     * Get total capacity across all group circles
     */
    protected function getTotalCapacity(): int
    {
        return $this->collection
            ->filter(fn ($circle) => $circle->resource instanceof \App\Models\QuranCircle)
            ->sum(fn ($circle) => $circle->max_students ?? 0);
    }

    /**
     * Get total students across all group circles
     */
    protected function getTotalStudents(): int
    {
        return $this->collection
            ->filter(fn ($circle) => $circle->resource instanceof \App\Models\QuranCircle)
            ->sum(fn ($circle) => $circle->current_students ?? 0);
    }
}
