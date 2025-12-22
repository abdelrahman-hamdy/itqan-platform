<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 1000);

        return [
            'academy_id' => Academy::factory(),
            'user_id' => User::factory()->student(),
            'payment_code' => 'PAY-' . fake()->unique()->randomNumber(6),
            'payment_method' => fake()->randomElement(['credit_card', 'mada', 'bank_transfer', 'cash']),
            'payment_gateway' => fake()->randomElement(['tap', 'moyasar', 'payfort', 'hyperpay', 'paytabs', 'manual']),
            'amount' => $amount,
            'currency' => 'SAR',
            'fees' => 0,
            'net_amount' => $amount,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_date' => now(),
            // Gateway integration fields - null by default
            'gateway_transaction_id' => null,
            'gateway_intent_id' => null,
            'gateway_order_id' => null,
            'client_secret' => null,
            'redirect_url' => null,
            'iframe_url' => null,
            'paid_at' => null,
            'refund_amount' => null,
        ];
    }

    /**
     * Configure a completed payment
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'payment_status' => 'paid',
            'confirmed_at' => now(),
            'paid_at' => now(),
        ]);
    }

    /**
     * Configure a failed payment
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'payment_status' => 'failed',
            'failure_reason' => 'Card declined',
        ]);
    }

    /**
     * Configure a refunded payment
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_amount' => $attributes['amount'] ?? 100,
        ]);
    }

    /**
     * Configure for a specific academy
     */
    public function forAcademy(Academy|int $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy instanceof Academy ? $academy->id : $academy,
        ]);
    }
}
