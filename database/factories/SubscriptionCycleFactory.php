<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Minimal SubscriptionCycle factory created for Phase B test suite.
 *
 * Default state: an active, paid, mid-month cycle with package source — the
 * canonical happy-path shape under the v2 contract (INV-A1, INV-D1).
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionCycle>
 */
class SubscriptionCycleFactory extends Factory
{
    protected $model = SubscriptionCycle::class;

    public function definition(): array
    {
        $startsAt = now()->copy()->subDays(5);
        $endsAt = $startsAt->copy()->addMonth();

        return [
            'subscribable_type' => (new QuranSubscription)->getMorphClass(),
            'subscribable_id' => QuranSubscription::factory(),
            'academy_id' => Academy::factory(),
            'cycle_number' => 1,
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'billing_cycle' => 'monthly',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'sessions_missed' => 0,
            'carryover_sessions' => 0,
            'total_price' => 200,
            'discount_amount' => 0,
            'final_price' => 200,
            'currency' => 'SAR',
            'package_id' => null,
            'package_snapshot' => null,
            'payment_id' => null,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'pricing_source' => 'package',
            'pricing_override_reason' => null,
            'pricing_override_actor_id' => null,
            'grace_period_ends_at' => null,
            'archived_at' => null,
            'metadata' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'cycle_number' => 2,
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonths(2),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'cycle_state' => SubscriptionCycle::STATE_ARCHIVED,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
            'archived_at' => now()->subMonth(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => SubscriptionCycle::PAYMENT_FAILED,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function inGrace(int $graceDays = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDays($graceDays),
        ]);
    }

    public function withPackage(int $packageId, ?array $snapshot = null): static
    {
        return $this->state(fn (array $attributes) => [
            'package_id' => $packageId,
            'package_snapshot' => $snapshot,
            'pricing_source' => 'package',
        ]);
    }

    public function manualOverride(string $reason, int $actorId, float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_source' => 'manual_override',
            'pricing_override_reason' => $reason,
            'pricing_override_actor_id' => $actorId,
            'final_price' => $price,
        ]);
    }
}
