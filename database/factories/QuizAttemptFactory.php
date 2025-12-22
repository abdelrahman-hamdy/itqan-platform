<?php

namespace Database\Factories;

use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_assignment_id' => QuizAssignment::factory(),
            'student_id' => StudentProfile::factory(),
            'answers' => [],
            'score' => null,
            'passed' => false,
            'started_at' => now(),
            'submitted_at' => null,
        ];
    }

    /**
     * Indicate the attempt is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 100),
            'passed' => fake()->boolean(),
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate the attempt is passed.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(70, 100),
            'passed' => true,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate the attempt is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 69),
            'passed' => false,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate the attempt is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_at' => null,
        ]);
    }
}
