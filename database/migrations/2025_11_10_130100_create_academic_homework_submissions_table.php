<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_homework_submissions', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->foreignId('academic_homework_id')->constrained('academic_homework')->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            // Submission Content
            $table->longText('submission_text')->nullable();
            $table->json('submission_files')->nullable();
            $table->text('submission_notes')->nullable();
            $table->json('revision_history')->nullable();

            // Submission Status
            $table->enum('submission_status', [
                'not_submitted',
                'draft',
                'submitted',
                'late',
                'pending_review',
                'under_review',
                'graded',
                'returned',
                'revision_requested',
                'resubmitted'
            ])->default('not_submitted');

            $table->dateTime('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->integer('days_late')->default(0);
            $table->integer('submission_attempt')->default(1);
            $table->integer('revision_count')->default(0);

            // Grading
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->string('grade_letter')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->json('grading_breakdown')->nullable();
            $table->boolean('late_penalty_applied')->default(false);
            $table->decimal('late_penalty_amount', 5, 2)->default(0);
            $table->decimal('bonus_points', 5, 2)->default(0);

            // Grading Audit
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('graded_at')->nullable();
            $table->dateTime('returned_at')->nullable();

            // Quality Scores
            $table->decimal('content_quality_score', 5, 2)->nullable();
            $table->decimal('presentation_score', 5, 2)->nullable();
            $table->decimal('effort_score', 5, 2)->nullable();
            $table->decimal('creativity_score', 5, 2)->nullable();

            // Time Tracking
            $table->integer('time_spent_minutes')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('last_edited_at')->nullable();

            // Student Self-Assessment
            $table->text('student_reflection')->nullable();
            $table->enum('student_difficulty_rating', ['very_easy', 'easy', 'moderate', 'difficult', 'very_difficult'])->nullable();
            $table->integer('student_time_estimate_minutes')->nullable();
            $table->text('student_questions')->nullable();

            // Flags
            $table->boolean('requires_follow_up')->default(false);
            $table->boolean('teacher_reviewed')->default(false);
            $table->boolean('parent_notified')->default(false);
            $table->boolean('flagged_for_review')->default(false);
            $table->text('flag_reason')->nullable();

            // Parent Review
            $table->boolean('parent_viewed')->default(false);
            $table->dateTime('parent_viewed_at')->nullable();
            $table->text('parent_feedback')->nullable();
            $table->boolean('parent_signature')->default(false);

            // Plagiarism Check
            $table->boolean('plagiarism_checked')->default(false);
            $table->decimal('originality_score', 5, 2)->nullable();
            $table->text('plagiarism_notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['academy_id', 'student_id'], 'idx_academy_student');
            $table->index(['academic_homework_id', 'student_id'], 'idx_homework_student');
            $table->index(['academic_session_id', 'student_id'], 'idx_session_student');
            $table->index(['submission_status'], 'idx_submission_status');
            $table->index(['is_late'], 'idx_is_late');
            $table->index(['submitted_at'], 'idx_submitted_at');
            $table->index(['graded_at'], 'idx_graded_at');

            // Unique constraint: One submission per student per homework
            $table->unique(['academic_homework_id', 'student_id', 'submission_attempt'], 'unique_homework_student_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_homework_submissions');
    }
};
