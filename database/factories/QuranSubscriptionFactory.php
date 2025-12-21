<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranSubscription>
 */
class QuranSubscriptionFactory extends Factory
{
    protected $model = QuranSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now();
        $endDate = $startDate->copy()->addMonth();

        return [
            'academy_id' => Academy::factory(),
            'student_id' => User::factory()->student(),
            'quran_teacher_id' => User::factory()->quranTeacher(),
            'package_name' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'sessions_per_month' => fake()->randomElement([4, 8, 12, 16]),
            'session_duration_minutes' => fake()->randomElement([30, 45, 60]),
            'price' => fake()->randomFloat(2, 100, 500),
            'currency' => 'SAR',
            'status' => SubscriptionStatus::ACTIVE,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sessions_used' => 0,
            'sessions_remaining' => fake()->randomElement([4, 8, 12, 16]),
            'auto_renew' => true,
        ];
    }

    /**
     * Create an active subscription.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
        ]);
    }

    /**
     * Create an expired subscription.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::EXPIRED,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
        ]);
    }

    /**
     * Create a cancelled subscription.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Create a pending subscription.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PENDING,
        ]);
    }

    /**
     * Create a subscription with all sessions used.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'sessions_used' => $attributes['sessions_per_month'] ?? 8,
            'sessions_remaining' => 0,
        ]);
    }

    /**
     * Create a subscription for a specific student.
     */
    public function forStudent(User $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
            'academy_id' => $student->academy_id,
        ]);
    }

    /**
     * Create a subscription for a specific teacher.
     */
    public function forTeacher(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'quran_teacher_id' => $teacher->id,
            'academy_id' => $teacher->academy_id,
        ]);
    }

    /**
     * Create a trial subscription.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'package_name' => 'Trial',
            'sessions_per_month' => 1,
            'price' => 0,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);
    }
}
