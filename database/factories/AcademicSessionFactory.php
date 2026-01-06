<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSession>
 */
class AcademicSessionFactory extends Factory
{
    protected $model = AcademicSession::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'academic_teacher_id' => AcademicTeacherProfile::factory(),
            'student_id' => User::factory()->student(),
            'title' => fake()->sentence(3),
            'session_type' => 'individual',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'duration_minutes' => 60,
            'subscription_counted' => false,
            'recording_enabled' => false,
            'homework_assigned' => false,
        ];
    }

    /**
     * Configure a scheduled session
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 14)),
        ]);
    }

    /**
     * Configure a completed session
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subDays(fake()->numberBetween(1, 7)),
            'started_at' => now()->subDays(1),
            'ended_at' => now()->subDays(1)->addMinutes(60),
        ]);
    }

    /**
     * Configure a cancelled session
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => fake()->sentence(),
            'cancelled_at' => now(),
        ]);
    }
}
