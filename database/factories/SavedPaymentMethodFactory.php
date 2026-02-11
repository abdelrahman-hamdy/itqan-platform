<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedPaymentMethod>
 */
class SavedPaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SavedPaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'academy_id' => Academy::factory(),
            'gateway' => 'paymob',
            'token' => 'tok_test_'.fake()->unique()->uuid(),
            'type' => 'card',
            'last_four' => (string) fake()->numberBetween(1000, 9999),
            'brand' => fake()->randomElement(['visa', 'mastercard', 'mada']),
            'holder_name' => fake()->name(),
            'expiry_month' => fake()->numberBetween(1, 12),
            'expiry_year' => now()->addYears(2)->year,
            'expires_at' => now()->addYear(),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the payment method is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMonth(),
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the payment method is the default one.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
