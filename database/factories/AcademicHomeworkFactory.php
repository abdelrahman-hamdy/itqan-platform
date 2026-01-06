<?php

namespace Database\Factories;

use App\Models\AcademicHomework;
use App\Models\AcademicSession;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicHomework>
 */
class AcademicHomeworkFactory extends Factory
{
    protected $model = AcademicHomework::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'academic_session_id' => AcademicSession::factory(),
            'teacher_id' => User::factory()->academicTeacher(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'due_date' => now()->addWeek(),
            'assigned_at' => now(),
            'max_score' => 100,
            'status' => 'published',
            'is_active' => true,
            'is_mandatory' => true,
            'allow_late_submissions' => true,
            'submission_type' => 'both',
            'max_files' => 5,
            'max_file_size_mb' => 10,
            'priority' => 'medium',
            'grading_scale' => 'points',
        ];
    }

    /**
     * Mark as draft
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_active' => false,
        ]);
    }

    /**
     * Mark as mandatory
     */
    public function mandatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => true,
        ]);
    }

    /**
     * Mark as optional
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => false,
        ]);
    }

    /**
     * Set as overdue
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays(3),
            'assigned_at' => now()->subWeek(),
        ]);
    }
}
