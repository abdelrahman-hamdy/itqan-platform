<?php

namespace App\Http\Resources\Api\V1\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Quiz Result Resource
 *
 * Quiz attempt and result data.
 *
 * @mixin QuizAttempt
 */
class QuizResultResource extends JsonResource
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

            // Quiz
            'quiz' => $this->whenLoaded('quiz', [
                'id' => $this->resource->quiz?->id,
                'title' => $this->resource->quiz?->title,
                'total_marks' => (float) $this->resource->quiz?->total_marks,
                'passing_marks' => (float) $this->resource->quiz?->passing_marks,
            ]),

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->resource->student?->id,
                'name' => $this->resource->student?->user?->name,
                'student_code' => $this->resource->student?->student_code,
            ]),

            // Attempt details
            'attempt_number' => $this->resource->attempt_number,

            // Timing
            'started_at' => $this->resource->started_at?->toISOString(),
            'completed_at' => $this->resource->completed_at?->toISOString(),
            'time_taken_minutes' => $this->resource->time_taken_minutes,

            // Scores
            'score' => (float) $this->resource->score,
            'percentage' => (float) $this->resource->percentage,
            'passed' => $this->resource->passed,

            // Answers
            'total_questions' => $this->resource->total_questions,
            'correct_answers' => $this->resource->correct_answers,
            'incorrect_answers' => $this->resource->incorrect_answers,
            'unanswered' => $this->resource->unanswered,

            // Detailed answers (only if quiz allows showing correct answers)
            'answers' => $this->when(
                $this->resource->quiz?->show_correct_answers && $this->resource->completed_at,
                $this->resource->answers
            ),

            // Feedback
            'feedback' => $this->resource->feedback,

            // Status
            'is_completed' => (bool) $this->resource->completed_at,

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
