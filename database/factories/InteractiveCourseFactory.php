<?php

namespace Database\Factories;

use App\Enums\InteractiveCourseStatus;
use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InteractiveCourse>
 */
class InteractiveCourseFactory extends Factory
{
    protected $model = InteractiveCourse::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'assigned_teacher_id' => AcademicTeacherProfile::factory(),
            'subject_id' => AcademicSubject::factory(),
            'grade_level_id' => AcademicGradeLevel::factory(),
            'title' => fake()->sentence(3),
            'title_en' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'description_en' => fake()->paragraph(),
            'course_type' => fake()->randomElement(['intensive', 'regular', 'exam_prep']),
            'difficulty_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'max_students' => fake()->numberBetween(10, 30),
            'duration_weeks' => fake()->numberBetween(4, 16),
            'sessions_per_week' => fake()->numberBetween(1, 3),
            'session_duration_minutes' => fake()->randomElement([45, 60, 90]),
            'total_sessions' => fake()->numberBetween(8, 24),
            'student_price' => fake()->randomFloat(2, 200, 2000),
            'teacher_payment' => fake()->randomFloat(2, 100, 500),
            'payment_type' => fake()->randomElement(['fixed_amount', 'per_student', 'per_session']),
            'start_date' => now()->addDays(7),
            'enrollment_deadline' => now()->addDays(5),
            'schedule' => [
                ['day' => 'sunday', 'start_time' => '10:00', 'end_time' => '11:00'],
                ['day' => 'tuesday', 'start_time' => '10:00', 'end_time' => '11:00'],
            ],
            'status' => InteractiveCourseStatus::PUBLISHED,
            'is_published' => false,
            'certificate_enabled' => true,
            'recording_enabled' => false,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Configure a published course
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InteractiveCourseStatus::PUBLISHED,
            'is_published' => true,
            'publication_date' => now(),
        ]);
    }

    /**
     * Configure an active course
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InteractiveCourseStatus::ACTIVE,
            'is_published' => true,
            'start_date' => now()->subDays(7),
        ]);
    }
}
