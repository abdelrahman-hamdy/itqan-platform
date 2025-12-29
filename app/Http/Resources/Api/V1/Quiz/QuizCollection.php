<?php

namespace App\Http\Resources\Api\V1\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Quiz Collection Resource
 *
 * Collection wrapper for quiz resources with statistics
 */
class QuizCollection extends ResourceCollection
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
                'published_count' => $this->getPublishedCount(),
                'difficulty_levels' => $this->getDifficultyBreakdown(),
                'statistics' => [
                    'total_questions' => $this->getTotalQuestions(),
                    'average_duration' => $this->getAverageDuration(),
                    'average_passing_marks' => $this->getAveragePassingMarks(),
                ],
            ],
        ];
    }

    /**
     * Get count of published quizzes
     *
     * @return int
     */
    protected function getPublishedCount(): int
    {
        return $this->collection->where('is_published', true)->count();
    }

    /**
     * Get breakdown of quizzes by difficulty level
     *
     * @return array
     */
    protected function getDifficultyBreakdown(): array
    {
        return $this->collection->groupBy(fn($quiz) => $quiz->difficulty_level?->value ?? 'N/A')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Get total questions across all quizzes
     *
     * @return int
     */
    protected function getTotalQuestions(): int
    {
        return $this->collection->sum(fn($quiz) => $quiz->total_questions ?? 0);
    }

    /**
     * Get average duration in minutes
     *
     * @return float|null
     */
    protected function getAverageDuration(): ?float
    {
        $durations = $this->collection->pluck('duration_minutes')->filter();

        if ($durations->isEmpty()) {
            return null;
        }

        return round($durations->average(), 2);
    }

    /**
     * Get average passing marks
     *
     * @return float|null
     */
    protected function getAveragePassingMarks(): ?float
    {
        $passingMarks = $this->collection->pluck('passing_marks')->filter();

        if ($passingMarks->isEmpty()) {
            return null;
        }

        return round($passingMarks->average(), 2);
    }
}
