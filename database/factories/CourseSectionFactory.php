<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\RecordedCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseSectionFactory extends Factory
{
    protected $model = CourseSection::class;

    public function definition(): array
    {
        return [
            'recorded_course_id' => RecordedCourse::factory(),
            'title' => $this->faker->sentence(3),
            'title_en' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'description_en' => $this->faker->paragraph(),
            'order' => $this->faker->numberBetween(1, 10),
            'is_published' => true,
            'is_free_preview' => false,
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'lessons_count' => 0,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
