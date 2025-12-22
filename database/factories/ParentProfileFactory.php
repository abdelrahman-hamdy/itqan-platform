<?php

namespace Database\Factories;

use App\Enums\RelationshipType;
use App\Models\Academy;
use App\Models\ParentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParentProfile>
 */
class ParentProfileFactory extends Factory
{
    protected $model = ParentProfile::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('05########'),
            'preferred_contact_method' => fake()->randomElement(['phone', 'email', 'sms']),
        ];
    }

    /**
     * Configure linked to user
     */
    public function linked(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->parent(),
        ]);
    }
}
