<?php

namespace App\Http\Resources\Api\V1\Homework;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Homework Collection Resource
 *
 * Collection wrapper for homework resources with statistics
 */
class HomeworkCollection extends ResourceCollection
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
                'statuses' => $this->getStatusBreakdown(),
                'statistics' => [
                    'submitted_count' => $this->getSubmittedCount(),
                    'graded_count' => $this->getGradedCount(),
                    'pending_count' => $this->getPendingCount(),
                    'average_grade' => $this->getAverageGrade(),
                ],
            ],
        ];
    }

    /**
     * Get breakdown of homework by status
     *
     * @return array
     */
    protected function getStatusBreakdown(): array
    {
        return $this->collection->groupBy(fn($homework) => $homework->status->value)
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Get count of submitted homework
     *
     * @return int
     */
    protected function getSubmittedCount(): int
    {
        return $this->collection->whereIn('status.value', ['submitted', 'graded'])->count();
    }

    /**
     * Get count of graded homework
     *
     * @return int
     */
    protected function getGradedCount(): int
    {
        return $this->collection->where('status.value', 'graded')->count();
    }

    /**
     * Get count of pending homework
     *
     * @return int
     */
    protected function getPendingCount(): int
    {
        return $this->collection->where('status.value', 'pending')->count();
    }

    /**
     * Get average grade across graded homework
     *
     * @return float|null
     */
    protected function getAverageGrade(): ?float
    {
        $graded = $this->collection->filter(fn($homework) => $homework->grade !== null);

        if ($graded->isEmpty()) {
            return null;
        }

        return round($graded->avg('grade'), 2);
    }
}
