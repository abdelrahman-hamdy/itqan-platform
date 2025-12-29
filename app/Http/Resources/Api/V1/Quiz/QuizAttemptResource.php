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
            'id' => $this->id,
            'attempt_number' => $this->attempt_number,

            // Quiz reference
            'quiz' => $this->whenLoaded('quiz', function () {
                return [
                    'id' => $this->quiz?->id,
                    'title' => $this->quiz?->title,
                    'total_marks' => (float) $this->quiz?->total_marks,
                    'passing_marks' => (float) $this->quiz?->passing_marks,
                ];
            }),

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->student?->id,
                'name' => $this->student?->user?->name,
                'student_code' => $this->student?->student_code,
            ]),

            // Timing
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'time_taken_minutes' => $this->time_taken_minutes,
            'time_remaining_minutes' => $this->when(
                !$this->completed_at && $this->quiz?->duration_minutes,
                fn() => $this->calculateRemainingTime()
            ),

            // Scoring
            'score' => [
                'marks_obtained' => (float) $this->marks_obtained,
                'total_marks' => (float) $this->total_marks,
                'percentage' => (float) $this->percentage,
                'passed' => $this->passed,
                'grade' => $this->grade,
            ],

            // Status
            'status' => $this->status,
            'is_completed' => $this->completed_at !== null,

            // Answers (conditionally shown)
            'answers' => $this->when(
                $this->completed_at || $request->user()?->isTeacher(),
                $this->answers
            ),

            // Feedback
            'feedback' => $this->feedback,
            'teacher_comments' => $this->teacher_comments,

            // Auto-grading
            'auto_graded' => $this->auto_graded,
            'graded_at' => $this->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->gradedBy?->id,
                'name' => $this->gradedBy?->name,
            ]),

            // Question statistics
            'statistics' => [
                'total_questions' => $this->total_questions,
                'correct_answers' => $this->correct_answers,
                'incorrect_answers' => $this->incorrect_answers,
                'unanswered' => $this->unanswered,
                'accuracy_percentage' => $this->accuracy_percentage,
            ],

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Calculate remaining time for in-progress attempt
     *
     * @return int|null
     */
    protected function calculateRemainingTime(): ?int
    {
        if (!$this->started_at || !$this->quiz?->duration_minutes) {
            return null;
        }

        $elapsedMinutes = now()->diffInMinutes($this->started_at);
        $remaining = $this->quiz->duration_minutes - $elapsedMinutes;

        return max(0, $remaining);
    }
}
