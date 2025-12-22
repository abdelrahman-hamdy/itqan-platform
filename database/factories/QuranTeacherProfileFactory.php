<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranTeacherProfile>
 */
class QuranTeacherProfileFactory extends Factory
{
    protected $model = QuranTeacherProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'user_id' => null, // Will be linked later
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('05########'),
            'educational_qualification' => fake()->randomElement(['bachelor', 'master', 'phd', 'other']),
            'teaching_experience_years' => fake()->numberBetween(1, 20),
            'available_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
            'available_time_start' => '08:00',
            'available_time_end' => '20:00',
            'languages' => ['arabic'],
            'is_active' => true,
            'approval_status' => 'approved',
            'offers_trial_sessions' => true,
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            'total_reviews' => fake()->numberBetween(0, 50),
            'total_students' => fake()->numberBetween(0, 100),
            'total_sessions' => fake()->numberBetween(0, 500),
            'session_price_individual' => fake()->randomFloat(2, 30, 100),
            'session_price_group' => fake()->randomFloat(2, 20, 60),
        ];
    }

    /**
     * Indicate that the teacher is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the teacher is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the teacher does not offer trial sessions.
     */
    public function noTrialSessions(): static
    {
        return $this->state(fn (array $attributes) => [
            'offers_trial_sessions' => false,
        ]);
    }

    /**
     * Create a teacher with an associated user.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $user = $user ?? User::factory()->create([
                'academy_id' => $attributes['academy_id'],
                'user_type' => 'quran_teacher',
                'email' => $attributes['email'],
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
            ]);

            return [
                'user_id' => $user->id,
                'academy_id' => $user->academy_id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];
        });
    }

    /**
     * Create a highly rated teacher.
     */
    public function highlyRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomFloat(2, 4.5, 5.0),
            'total_reviews' => fake()->numberBetween(20, 100),
        ]);
    }

    /**
     * Create an experienced teacher.
     */
    public function experienced(): static
    {
        return $this->state(fn (array $attributes) => [
            'teaching_experience_years' => fake()->numberBetween(10, 25),
            'total_sessions' => fake()->numberBetween(500, 2000),
            'total_students' => fake()->numberBetween(100, 300),
        ]);
    }
}
