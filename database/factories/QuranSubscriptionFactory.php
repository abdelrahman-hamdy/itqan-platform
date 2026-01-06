<?php

namespace Database\Factories;

use App\Enums\SessionSubscriptionStatus;
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

        $totalSessions = fake()->randomElement([4, 8, 12, 16]);
        $totalPrice = fake()->randomFloat(2, 100, 500);

        return [
            'academy_id' => Academy::factory(),
            'student_id' => User::factory()->student(),
            'quran_teacher_id' => User::factory()->quranTeacher(),
            'package_name_ar' => fake()->randomElement(['الأساسي', 'المتوسط', 'المتميز']),
            'package_name_en' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'package_sessions_per_week' => fake()->randomElement([1, 2, 3, 4]),
            'package_session_duration_minutes' => fake()->randomElement([30, 45, 60]),
            'total_sessions' => $totalSessions,
            'total_price' => $totalPrice,
            'currency' => 'SAR',
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'sessions_used' => 0,
            'sessions_remaining' => $totalSessions,
            'auto_renew' => true,
        ];
    }

    /**
     * Create an active subscription.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
        ]);
    }

    /**
     * Create an expired subscription.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionSubscriptionStatus::EXPIRED,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Create a cancelled subscription.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Create a pending subscription.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionSubscriptionStatus::PENDING,
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
            'package_name_ar' => 'تجريبي',
            'package_name_en' => 'Trial',
            'package_sessions_per_week' => 1,
            'total_sessions' => 1,
            'sessions_remaining' => 1,
            'total_price' => 0,
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
            'is_trial_active' => true,
        ]);
    }
}
