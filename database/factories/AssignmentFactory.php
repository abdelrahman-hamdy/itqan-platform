<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'course_id' => Course::factory(),
            'teacher_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructions' => fake()->optional()->paragraph(2),
            'type' => fake()->randomElement(['homework', 'quiz', 'project', 'essay']),
            'max_score' => fake()->randomElement([10, 20, 50, 100]),
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'is_active' => true,
            'allow_late_submission' => fake()->boolean(70),
            'late_penalty_percentage' => fake()->optional()->randomFloat(2, 5, 25),
        ];
    }

    /**
     * Active assignment.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
        ]);
    }

    /**
     * Inactive assignment.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Overdue assignment.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    /**
     * Due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->endOfDay(),
        ]);
    }

    /**
     * Assignment with late submission allowed.
     */
    public function allowsLateSubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_late_submission' => true,
            'late_penalty_percentage' => fake()->randomFloat(2, 5, 20),
        ]);
    }

    /**
     * Assignment that doesn't allow late submission.
     */
    public function noLateSubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_late_submission' => false,
            'late_penalty_percentage' => null,
        ]);
    }

    /**
     * Homework type.
     */
    public function homework(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'homework',
        ]);
    }

    /**
     * Quiz type.
     */
    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'quiz',
        ]);
    }

    /**
     * Project type.
     */
    public function project(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'project',
            'max_score' => 100,
        ]);
    }
}
