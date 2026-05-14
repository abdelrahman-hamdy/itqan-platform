<?php

namespace Database\Factories;

use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Minimal SessionConsumption factory created for Phase B test suite.
 *
 * Direct `SessionConsumption::factory()->create()` writes BYPASS the P5
 * precedence cascade — only tests + the bootstrap importer should use it.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionConsumption>
 */
class SessionConsumptionFactory extends Factory
{
    protected $model = SessionConsumption::class;

    public function definition(): array
    {
        return [
            'session_id' => QuranSession::factory(),
            'session_type' => (new QuranSession)->getMorphClass(),
            'subscription_id' => QuranSubscription::factory(),
            'subscription_type' => (new QuranSubscription)->getMorphClass(),
            'cycle_id' => SubscriptionCycle::factory(),
            'student_user_id' => User::factory(),
            'consumption_type' => SessionConsumption::TYPE_ATTENDED,
            'source' => SessionConsumption::SOURCE_TEACHER_REPORT,
            'source_user_id' => null,
            'consumed_at' => now(),
            'reversed_at' => null,
            'reversed_reason' => null,
            'reversed_by_user_id' => null,
        ];
    }

    public function autoAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        ]);
    }

    public function teacherReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => SessionConsumption::SOURCE_TEACHER_REPORT,
        ]);
    }

    public function adminManual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => SessionConsumption::SOURCE_ADMIN_MANUAL,
        ]);
    }

    public function reversed(string $reason = 'test reversal', ?int $reverserId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reversed_at' => now(),
            'reversed_reason' => $reason,
            'reversed_by_user_id' => $reverserId,
        ]);
    }
}
