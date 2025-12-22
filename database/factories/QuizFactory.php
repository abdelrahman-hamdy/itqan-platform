<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'passing_score' => fake()->randomElement([50, 60, 70, 80]),
            'is_active' => true,
        ];
    }

    /**
     * Indicate the quiz is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the quiz is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
