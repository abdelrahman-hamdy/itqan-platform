<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $course = RecordedCourse::factory()->create();
        $section = CourseSection::factory()->create([
            'recorded_course_id' => $course->id,
        ]);

        return [
            'recorded_course_id' => $course->id,
            'course_section_id' => $section->id,
            'title' => $this->faker->sentence(3),
            'title_en' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'description_en' => $this->faker->paragraph(),
            'video_url' => $this->faker->url(),
            'video_size_mb' => $this->faker->randomFloat(2, 10, 500),
            'video_quality' => $this->faker->randomElement(['480p', '720p', '1080p', '4K']),
            'transcript' => null,
            'attachments' => null,
            'is_published' => true,
            'is_free_preview' => false,
            'is_downloadable' => false,
            'quiz_id' => null,
            'assignment_requirements' => null,
            'learning_objectives' => null,
            'view_count' => 0,
            'avg_rating' => null,
            'total_comments' => 0,
            'created_by' => null,
            'updated_by' => null,
            'published_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function freePreview(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free_preview' => true,
        ]);
    }
}
