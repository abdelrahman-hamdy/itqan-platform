<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicIndividualLesson>
 */
class AcademicIndividualLessonFactory extends Factory
{
    protected $model = AcademicIndividualLesson::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'academic_teacher_id' => AcademicTeacherProfile::factory(),
            'student_id' => User::factory()->student(),
            'academic_subscription_id' => AcademicSubscription::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'Chemistry', 'Biology', 'Arabic', 'English']),
            'total_sessions' => fake()->numberBetween(8, 24),
            'sessions_scheduled' => 0,
            'sessions_completed' => 0,
            'sessions_remaining' => fake()->numberBetween(8, 24),
            'default_duration_minutes' => 60,
            'status' => LessonStatus::ACTIVE,
            'recording_enabled' => false,
        ];
    }

    /**
     * Configure an active lesson
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::ACTIVE,
        ]);
    }

    /**
     * Configure a completed lesson
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::COMPLETED,
            'completed_at' => now(),
            'sessions_completed' => $attributes['total_sessions'] ?? 12,
            'sessions_remaining' => 0,
        ]);
    }

    /**
     * Configure a pending lesson
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::PENDING,
        ]);
    }

    /**
     * Configure a cancelled lesson
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::CANCELLED,
        ]);
    }
}
