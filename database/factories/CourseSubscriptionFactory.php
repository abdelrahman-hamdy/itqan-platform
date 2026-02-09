<?php

namespace Database\Factories;

use App\Enums\EnrollmentType;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseSubscription>
 */
class CourseSubscriptionFactory extends Factory
{
    protected $model = CourseSubscription::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'student_id' => User::factory()->state(['user_type' => 'student']),
            'subscription_code' => 'CS-'.rand(1, 999).'-'.now()->format('dmy').'-'.strtoupper(Str::random(4)),
            'course_type' => 'recorded', // varchar, not enum
            'recorded_course_id' => RecordedCourse::factory(),
            'enrollment_type' => EnrollmentType::PAID,
            'status' => 'active', // Valid enum: active, completed, paused, expired, cancelled, refunded
            'payment_status' => 'paid', // Valid enum: pending, paid, failed, refunded
            'currency' => 'SAR',
            'billing_cycle' => 'lifetime',
            'auto_renew' => false,
            'progress_percentage' => 0,
            'certificate_issued' => false,
            'lifetime_access' => true,
            'completed_lessons' => 0,
            'total_lessons' => fake()->numberBetween(5, 30),
            'total_duration_minutes' => 0,
            'attendance_count' => 0,
            'total_possible_attendance' => 0,
            'quiz_attempts' => 0,
            'quiz_passed' => false,
            'notes_count' => 0,
            'bookmarks_count' => 0,
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
            'payment_status' => 'paid',
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
     * Create a paused subscription
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Create an expired subscription
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
        ]);
    }

    /**
     * Create a cancelled subscription
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create a free enrollment
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => EnrollmentType::FREE,
            'payment_status' => 'paid', // Free means paid
        ]);
    }

    /**
     * Create a trial enrollment
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => EnrollmentType::TRIAL,
        ]);
    }

    /**
     * Set for interactive course
     */
    public function interactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_type' => 'interactive',
            'recorded_course_id' => null,
            'interactive_course_id' => InteractiveCourse::factory(),
            'lifetime_access' => false,
        ]);
    }

    /**
     * Set for recorded course
     */
    public function recorded(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_type' => 'recorded',
            'lifetime_access' => true,
        ]);
    }

    /**
     * Configure with specific student
     */
    public function withStudent(User $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }
}
