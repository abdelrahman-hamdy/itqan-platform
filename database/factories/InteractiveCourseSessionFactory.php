<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InteractiveCourseSession>
 */
class InteractiveCourseSessionFactory extends Factory
{
    protected $model = InteractiveCourseSession::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'course_id' => InteractiveCourse::factory(),
            'title' => fake()->sentence(3),
            'session_number' => fake()->numberBetween(1, 20),
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'attendance_count' => 0,
            'homework_assigned' => false,
        ];
    }
}
