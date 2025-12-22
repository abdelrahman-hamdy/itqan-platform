<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAssignment>
 */
class QuizAssignmentFactory extends Factory
{
    protected $model = QuizAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'assignable_type' => null,
            'assignable_id' => null,
            'is_visible' => true,
            'available_from' => null,
            'available_until' => null,
            'max_attempts' => 1,
        ];
    }

    /**
     * Indicate the quiz assignment is not visible.
     */
    public function invisible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }

    /**
     * Indicate the quiz assignment has availability dates.
     */
    public function withAvailability(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_from' => now(),
            'available_until' => now()->addDays(7),
        ]);
    }

    /**
     * Set multiple attempts.
     */
    public function multipleAttempts(int $attempts = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attempts' => $attempts,
        ]);
    }
}
