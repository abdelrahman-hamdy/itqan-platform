<?php

namespace Database\Factories;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\Academy;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicHomeworkSubmission>
 */
class AcademicHomeworkSubmissionFactory extends Factory
{
    protected $model = AcademicHomeworkSubmission::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'academic_homework_id' => AcademicHomework::factory(),
            'academic_session_id' => AcademicSession::factory(),
            'student_id' => User::factory()->student(),
            'submission_status' => HomeworkSubmissionStatus::NOT_STARTED,
            'max_score' => 100,
            'is_late' => false,
            'days_late' => 0,
            'submission_attempt' => 1,
            'revision_count' => 0,
        ];
    }

    /**
     * Mark as draft
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_status' => HomeworkSubmissionStatus::DRAFT,
            'submission_text' => fake()->paragraph(),
        ]);
    }

    /**
     * Mark as submitted
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_status' => HomeworkSubmissionStatus::SUBMITTED,
            'submitted_at' => now(),
            'submission_text' => fake()->paragraphs(2, true),
        ]);
    }

    /**
     * Mark as graded
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_status' => HomeworkSubmissionStatus::GRADED,
            'submitted_at' => now()->subDay(),
            'graded_at' => now(),
            'score' => fake()->randomFloat(2, 60, 100),
            'score_percentage' => fake()->randomFloat(2, 60, 100),
            'teacher_feedback' => fake()->sentence(),
        ]);
    }

    /**
     * Mark as late
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_status' => HomeworkSubmissionStatus::LATE,
            'submitted_at' => now(),
            'is_late' => true,
            'days_late' => fake()->numberBetween(1, 5),
            'late_penalty_applied' => true,
            'late_penalty_amount' => 10,
        ]);
    }

    /**
     * Mark as returned for revision
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_status' => HomeworkSubmissionStatus::RETURNED,
            'returned_at' => now(),
            'revision_count' => 1,
            'teacher_feedback' => fake()->sentence(),
        ]);
    }

    /**
     * Mark as resubmitted
     */
    public function resubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_status' => HomeworkSubmissionStatus::RESUBMITTED,
            'submitted_at' => now(),
            'revision_count' => 1,
            'submission_text' => fake()->paragraphs(2, true),
        ]);
    }
}
