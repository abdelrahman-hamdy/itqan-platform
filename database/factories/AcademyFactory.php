<?php

namespace Database\Factories;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Academy>
 */
class AcademyFactory extends Factory
{
    protected $model = Academy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Academy',
            'subdomain' => fake()->unique()->slug(2),
            'is_active' => true,
            'maintenance_mode' => false,
        ];
    }

    /**
     * Indicate the academy is in maintenance mode.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'maintenance_mode' => true,
        ]);
    }

    /**
     * Indicate the academy is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
