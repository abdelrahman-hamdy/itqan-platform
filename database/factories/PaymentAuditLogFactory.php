<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentAuditLog>
 */
class PaymentAuditLogFactory extends Factory
{
    protected $model = PaymentAuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['created', 'status_changed', 'webhook_received', 'refunded', 'attempt_initiated', 'attempt_failed'];
        $statuses = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'];

        return [
            'academy_id' => Academy::factory(),
            'payment_id' => Payment::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement($actions),
            'gateway' => fake()->randomElement(['paymob', 'tap', 'stripe']),
            'status_from' => fake()->randomElement($statuses),
            'status_to' => fake()->randomElement($statuses),
            'amount_cents' => rand(1000, 100000), // 10 - 1000 currency units
            'currency' => 'SAR',
            'transaction_id' => 'TXN_' . fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [
                'source' => fake()->randomElement(['web', 'api', 'webhook']),
                'notes' => fake()->optional()->sentence(),
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Payment creation audit log.
     */
    public function creation(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'created',
            'status_from' => null,
            'status_to' => 'pending',
        ]);
    }

    /**
     * Status change audit log.
     */
    public function statusChange(string $from = 'pending', string $to = 'completed'): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'status_changed',
            'status_from' => $from,
            'status_to' => $to,
        ]);
    }

    /**
     * Webhook received audit log.
     */
    public function webhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'webhook_received',
            'user_id' => null,
            'metadata' => [
                'payload_summary' => [
                    'success' => true,
                    'transaction_id' => 'TXN_' . fake()->uuid(),
                ],
            ],
        ]);
    }

    /**
     * Refund audit log.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'refunded',
            'status_from' => 'completed',
            'status_to' => 'refunded',
        ]);
    }

    /**
     * Failed attempt audit log.
     */
    public function failedAttempt(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'attempt_failed',
            'notes' => fake()->sentence(),
        ]);
    }
}
