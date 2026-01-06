<?php

namespace Database\Factories;

use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSubscription>
 */
class AcademicSubscriptionFactory extends Factory
{
    protected $model = AcademicSubscription::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'student_id' => User::factory()->state(['user_type' => 'student']),
            'teacher_id' => AcademicTeacherProfile::factory(),
            'subject_id' => AcademicSubject::factory(),
            'subject_name' => fake()->randomElement(['اللغة العربية', 'الرياضيات', 'العلوم', 'اللغة الإنجليزية']),
            'subscription_code' => 'ACD-'.rand(1, 999).'-'.strtoupper(Str::random(6)),
            'subscription_type' => 'private', // Valid enum: private, group
            'sessions_per_week' => fake()->numberBetween(1, 5),
            'session_duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'monthly_amount' => fake()->randomFloat(2, 200, 2000),
            'final_monthly_amount' => fake()->randomFloat(2, 200, 2000),
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'starts_at' => now(),
            'ends_at' => now()->addMonths(3),
            'next_billing_date' => now()->addMonth(),
            'auto_create_google_meet' => true,
            'status' => 'active', // Valid enum: active, paused, suspended, cancelled, expired, completed
            'payment_status' => 'current', // Valid enum: current, pending, overdue, failed, refunded
            'certificate_issued' => false,
            'has_trial_session' => false,
            'trial_session_used' => false,
            'auto_renew' => true,
            'progress_percentage' => 0,
            'total_sessions_scheduled' => 0,
            'total_sessions_completed' => 0,
            'total_sessions_missed' => 0,
            'sessions_remaining' => 8, // Legacy field, kept for backwards compatibility
        ];
    }

    /**
     * Create subscription for a specific academy
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy->id,
        ]);
    }

    /**
     * Create an active subscription
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'payment_status' => 'current',
        ]);
    }

    /**
     * Create a paused subscription
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'paused_at' => now(),
        ]);
    }

    /**
     * Create a cancelled subscription
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Create an expired subscription
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'end_date' => now()->subDay(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Create a completed subscription
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100,
            'certificate_issued' => true,
        ]);
    }

    /**
     * Set subscription with pending payment
     */
    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Set subscription with failed payment (replaces overdue)
     */
    public function failedPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'failed',
        ]);
    }

    /**
     * Configure the subscription with specific teacher
     */
    public function withTeacher(User $teacher): static
    {
        return $this->state(function (array $attributes) use ($teacher) {
            $profile = $teacher->academicTeacherProfile;

            return [
                'teacher_id' => $profile ? $profile->id : AcademicTeacherProfile::factory()->create(['user_id' => $teacher->id])->id,
            ];
        });
    }

    /**
     * Configure the subscription with specific student
     */
    public function withStudent(User $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }
}
