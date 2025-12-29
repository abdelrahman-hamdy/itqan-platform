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
            'id' => $this->resource->id,

            // Quiz details
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'instructions' => $this->resource->instructions,

            // Quiz configuration
            'total_questions' => $this->resource->total_questions,
            'total_marks' => (float) $this->resource->total_marks,
            'passing_marks' => (float) $this->resource->passing_marks,
            'duration_minutes' => $this->resource->duration_minutes,

            // Difficulty
            'difficulty_level' => $this->when($this->resource->difficulty_level, [
                'value' => $this->resource->difficulty_level?->value,
                'label' => $this->resource->difficulty_level?->label(),
            ]),

            // Settings
            'is_published' => $this->resource->is_published,
            'shuffle_questions' => $this->resource->shuffle_questions,
            'show_correct_answers' => $this->resource->show_correct_answers,
            'allow_retake' => $this->resource->allow_retake,
            'max_attempts' => $this->resource->max_attempts,

            // Quizzable (polymorphic - lesson/course/session)
            'quizzable' => [
                'type' => $this->resource->quizzable_type,
                'id' => $this->resource->quizzable_id,
            ],

            // Questions
            'questions' => $this->whenLoaded('questions', function () {
                return $this->resource->questions->map(fn($question) => [
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
                'id' => $this->resource->creator?->id,
                'name' => $this->resource->creator?->name,
            ]),

            // Attempts count
            'attempts_count' => $this->when(
                $this->relationLoaded('attempts'),
                fn() => $this->resource->attempts->count()
            ),

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
