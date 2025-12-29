<?php

namespace App\Http\Resources\Api\V1\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Quiz Attempt Resource
 *
 * Quiz attempt data including score, answers, and completion status.
 *
 * @mixin QuizAttempt
 */
class QuizAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'attempt_number' => $this->resource->attempt_number,

            // Quiz reference
            'quiz' => $this->whenLoaded('quiz', function () {
                return [
                    'id' => $this->resource->quiz?->id,
                    'title' => $this->resource->quiz?->title,
                    'total_marks' => (float) $this->resource->quiz?->total_marks,
                    'passing_marks' => (float) $this->resource->quiz?->passing_marks,
                ];
            }),

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->resource->student?->id,
                'name' => $this->resource->student?->user?->name,
                'student_code' => $this->resource->student?->student_code,
            ]),

            // Timing
            'started_at' => $this->resource->started_at?->toISOString(),
            'completed_at' => $this->resource->completed_at?->toISOString(),
            'time_taken_minutes' => $this->resource->time_taken_minutes,
            'time_remaining_minutes' => $this->when(
                !$this->resource->completed_at && $this->resource->quiz?->duration_minutes,
                fn() => $this->calculateRemainingTime()
            ),

            // Scoring
            'score' => [
                'marks_obtained' => (float) $this->resource->marks_obtained,
                'total_marks' => (float) $this->resource->total_marks,
                'percentage' => (float) $this->resource->percentage,
                'passed' => $this->resource->passed,
                'grade' => $this->resource->grade,
            ],

            // Status
            'status' => $this->resource->status,
            'is_completed' => $this->resource->completed_at !== null,

            // Answers (conditionally shown)
            'answers' => $this->when(
                $this->resource->completed_at || $request->user()?->isTeacher(),
                $this->resource->answers
            ),

            // Feedback
            'feedback' => $this->resource->feedback,
            'teacher_comments' => $this->resource->teacher_comments,

            // Auto-grading
            'auto_graded' => $this->resource->auto_graded,
            'graded_at' => $this->resource->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->resource->gradedBy?->id,
                'name' => $this->resource->gradedBy?->name,
            ]),

            // Question statistics
            'statistics' => [
                'total_questions' => $this->resource->total_questions,
                'correct_answers' => $this->resource->correct_answers,
                'incorrect_answers' => $this->resource->incorrect_answers,
                'unanswered' => $this->resource->unanswered,
                'accuracy_percentage' => $this->resource->accuracy_percentage,
            ],

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }

    /**
     * Calculate remaining time for in-progress attempt
     *
     * @return int|null
     */
    protected function calculateRemainingTime(): ?int
    {
        if (!$this->resource->started_at || !$this->resource->quiz?->duration_minutes) {
            return null;
        }

        $elapsedMinutes = now()->diffInMinutes($this->resource->started_at);
        $remaining = $this->resource->quiz->duration_minutes - $elapsedMinutes;

        return max(0, $remaining);
    }
}
