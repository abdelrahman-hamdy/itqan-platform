<?php

namespace App\Http\Resources\Api\V1\Quiz;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Quiz Resource
 *
 * Quiz data for all quiz types.
 *
 * @mixin Quiz
 */
class QuizResource extends JsonResource
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

            // Quiz details
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,

            // Quiz configuration
            'total_questions' => $this->total_questions,
            'total_marks' => (float) $this->total_marks,
            'passing_marks' => (float) $this->passing_marks,
            'duration_minutes' => $this->duration_minutes,

            // Difficulty
            'difficulty_level' => $this->when($this->difficulty_level, [
                'value' => $this->difficulty_level?->value,
                'label' => $this->difficulty_level?->label(),
            ]),

            // Settings
            'is_published' => $this->is_published,
            'shuffle_questions' => $this->shuffle_questions,
            'show_correct_answers' => $this->show_correct_answers,
            'allow_retake' => $this->allow_retake,
            'max_attempts' => $this->max_attempts,

            // Quizzable (polymorphic - lesson/course/session)
            'quizzable' => [
                'type' => $this->quizzable_type,
                'id' => $this->quizzable_id,
            ],

            // Questions
            'questions' => $this->whenLoaded('questions', function () {
                return $this->questions->map(fn($question) => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => (float) $question->points,
                    'order' => $question->order,
                    'options' => $question->options,
                ]);
            }),

            // Creator
            'created_by' => $this->whenLoaded('creator', [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),

            // Attempts count
            'attempts_count' => $this->when(
                $this->relationLoaded('attempts'),
                fn() => $this->attempts->count()
            ),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
