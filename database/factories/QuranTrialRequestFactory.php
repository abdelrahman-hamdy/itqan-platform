<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranTrialRequest>
 */
class QuranTrialRequestFactory extends Factory
{
    protected $model = QuranTrialRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'request_code' => 'TR-' . strtoupper(Str::random(8)),
            'student_name' => fake()->name(),
            'student_age' => fake()->numberBetween(5, 60),
            'phone' => fake()->numerify('05########'),
            'email' => fake()->safeEmail(),
            'current_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'learning_goals' => fake()->paragraph(),
            'preferred_time' => fake()->randomElement(['morning', 'afternoon', 'evening']),
            'notes' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    /**
     * Pending status
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Approved status
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'teacher_id' => QuranTeacherProfile::factory(),
        ]);
    }

    /**
     * Completed status
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'rating' => fake()->numberBetween(3, 5),
            'feedback' => fake()->paragraph(),
        ]);
    }

    /**
     * For specific academy
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy->id,
        ]);
    }
}
