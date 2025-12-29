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
            'id' => $this->id,

            // Quiz
            'quiz' => $this->whenLoaded('quiz', [
                'id' => $this->quiz?->id,
                'title' => $this->quiz?->title,
                'total_marks' => (float) $this->quiz?->total_marks,
                'passing_marks' => (float) $this->quiz?->passing_marks,
            ]),

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->student?->id,
                'name' => $this->student?->user?->name,
                'student_code' => $this->student?->student_code,
            ]),

            // Attempt details
            'attempt_number' => $this->attempt_number,

            // Timing
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'time_taken_minutes' => $this->time_taken_minutes,

            // Scores
            'score' => (float) $this->score,
            'percentage' => (float) $this->percentage,
            'passed' => $this->passed,

            // Answers
            'total_questions' => $this->total_questions,
            'correct_answers' => $this->correct_answers,
            'incorrect_answers' => $this->incorrect_answers,
            'unanswered' => $this->unanswered,

            // Detailed answers (only if quiz allows showing correct answers)
            'answers' => $this->when(
                $this->quiz?->show_correct_answers && $this->completed_at,
                $this->answers
            ),

            // Feedback
            'feedback' => $this->feedback,

            // Status
            'is_completed' => (bool) $this->completed_at,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
