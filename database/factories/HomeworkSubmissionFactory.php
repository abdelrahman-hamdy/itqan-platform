<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\HomeworkSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HomeworkSubmission>
 */
class HomeworkSubmissionFactory extends Factory
{
    protected $model = HomeworkSubmission::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'submitable_type' => 'App\\Models\\QuranSession',
            'submitable_id' => 1,
            'homework_type' => 'memorization',
            'student_id' => User::factory()->state(['user_type' => 'student']),
            'submission_code' => 'HW-' . strtoupper(Str::random(8)),
            'submission_text' => fake()->paragraph(),
            'submitted_at' => now(),
            'is_late' => false,
            'days_late' => 0,
            'status' => 'submitted',
            'submission_status' => 'submitted',
            'progress_percentage' => 0,
        ];
    }

    /**
     * Submitted status
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submission_status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Graded status
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'submission_status' => 'graded',
            'graded_at' => now(),
            'graded_by' => User::factory(),
            'score' => fake()->numberBetween(60, 100),
            'max_score' => 100,
            'score_percentage' => fake()->numberBetween(60, 100),
            'grade_letter' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'teacher_feedback' => fake()->paragraph(),
        ]);
    }

    /**
     * Late submission
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_late' => true,
            'days_late' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * For specific academy
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy->id,
        ]);
    }
}
