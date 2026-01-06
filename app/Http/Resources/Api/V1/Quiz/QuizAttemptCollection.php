<?php

namespace App\Http\Resources\Api\V1\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Quiz Attempt Collection Resource
 *
 * Collection wrapper for quiz attempt resources with statistics
 */
class QuizAttemptCollection extends ResourceCollection
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
                'completed_count' => $this->getCompletedCount(),
                'in_progress_count' => $this->getInProgressCount(),
                'statistics' => [
                    'average_score' => $this->getAverageScore(),
                    'highest_score' => $this->getHighestScore(),
                    'lowest_score' => $this->getLowestScore(),
                    'pass_rate' => $this->getPassRate(),
                    'average_time_taken' => $this->getAverageTimeTaken(),
                ],
            ],
        ];
    }

    /**
     * Get count of completed attempts
     */
    protected function getCompletedCount(): int
    {
        return $this->collection->whereNotNull('completed_at')->count();
    }

    /**
     * Get count of in-progress attempts
     */
    protected function getInProgressCount(): int
    {
        return $this->collection->whereNull('completed_at')->count();
    }

    /**
     * Get average score percentage
     */
    protected function getAverageScore(): ?float
    {
        $completed = $this->collection->whereNotNull('completed_at');

        if ($completed->isEmpty()) {
            return null;
        }

        return round($completed->avg('percentage'), 2);
    }

    /**
     * Get highest score percentage
     */
    protected function getHighestScore(): ?float
    {
        $completed = $this->collection->whereNotNull('completed_at');

        if ($completed->isEmpty()) {
            return null;
        }

        return round($completed->max('percentage'), 2);
    }

    /**
     * Get lowest score percentage
     */
    protected function getLowestScore(): ?float
    {
        $completed = $this->collection->whereNotNull('completed_at');

        if ($completed->isEmpty()) {
            return null;
        }

        return round($completed->min('percentage'), 2);
    }

    /**
     * Get pass rate percentage
     */
    protected function getPassRate(): ?float
    {
        $completed = $this->collection->whereNotNull('completed_at');
        $total = $completed->count();

        if ($total === 0) {
            return null;
        }

        $passed = $completed->where('passed', true)->count();

        return round(($passed / $total) * 100, 2);
    }

    /**
     * Get average time taken in minutes
     */
    protected function getAverageTimeTaken(): ?float
    {
        $completed = $this->collection->whereNotNull('completed_at');
        $times = $completed->pluck('time_taken_minutes')->filter();

        if ($times->isEmpty()) {
            return null;
        }

        return round($times->average(), 2);
    }
}
