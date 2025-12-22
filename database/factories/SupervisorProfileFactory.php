<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupervisorProfile>
 */
class SupervisorProfileFactory extends Factory
{
    protected $model = SupervisorProfile::class;

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
            'hired_date' => now(),
            'salary' => fake()->randomFloat(2, 5000, 15000),
        ];
    }

    /**
     * Create a supervisor with an associated user.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $user = $user ?? User::factory()->create([
                'academy_id' => $attributes['academy_id'],
                'user_type' => 'supervisor',
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

}
