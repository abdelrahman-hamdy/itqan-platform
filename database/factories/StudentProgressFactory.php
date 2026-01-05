<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentProgressFactory extends Factory
{
    protected $model = StudentProgress::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $course = RecordedCourse::factory()->create([
            'academy_id' => $user->academy_id ?? Academy::factory()->create()->id,
        ]);
        $section = CourseSection::factory()->create([
            'recorded_course_id' => $course->id,
        ]);
        $lesson = Lesson::create([
            'recorded_course_id' => $course->id,
            'course_section_id' => $section->id,
            'title' => 'Test Lesson',
            'is_published' => true,
            'published_at' => now(),
        ]);

        return [
            'user_id' => $user->id,
            'recorded_course_id' => $course->id,
            'course_section_id' => $section->id,
            'lesson_id' => $lesson->id,
            'progress_type' => 'lesson',
            'progress_percentage' => 0,
            'total_time_seconds' => 300,
            'is_completed' => false,
            'completed_at' => null,
            'last_accessed_at' => now(),
            'current_position_seconds' => 0,
            'quiz_score' => null,
            'quiz_attempts' => 0,
            'notes' => null,
            'bookmarked_at' => null,
            'rating' => null,
            'review_text' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
            'progress_percentage' => $this->faker->numberBetween(1, 90),
            'current_position_seconds' => $this->faker->numberBetween(10, 250),
        ]);
    }
}
