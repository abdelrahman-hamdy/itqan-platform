<?php

namespace Database\Factories;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecordedCourse>
 */
class RecordedCourseFactory extends Factory
{
    protected $model = RecordedCourse::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'subject_id' => AcademicSubject::factory(),
            'grade_level_id' => AcademicGradeLevel::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'course_code' => 'RC-'.fake()->unique()->numberBetween(1000, 9999),
            'duration_hours' => fake()->numberBetween(5, 50),
            'language' => 'ar',
            'price' => fake()->randomFloat(2, 100, 1000),
            'is_published' => false,
            'is_featured' => false,
            'total_sections' => fake()->numberBetween(3, 12),
            'total_duration_minutes' => fake()->numberBetween(300, 3000),
            'avg_rating' => 0,
            'total_reviews' => 0,
            'total_enrollments' => 0,
            'difficulty_level' => fake()->randomElement(['very_easy', 'easy', 'medium', 'hard', 'very_hard']),
            'status' => 'draft',
        ];
    }

    /**
     * Configure a published course
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Configure an active course
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_published' => true,
        ]);
    }

    /**
     * Configure a featured course
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_published' => true,
        ]);
    }

    /**
     * Create for a specific academy
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy->id,
        ]);
    }
}
