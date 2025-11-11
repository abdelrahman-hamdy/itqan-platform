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
        Schema::create('academic_progresses', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('academic_subscriptions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('academic_teacher_profiles')->onDelete('set null');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null');

            // Identification
            $table->string('progress_code', 50)->unique()->nullable();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('last_session_date')->nullable();
            $table->date('next_session_date')->nullable();

            // Session Statistics
            $table->integer('total_sessions_planned')->default(0);
            $table->integer('total_sessions_completed')->default(0);
            $table->integer('total_sessions_missed')->default(0);
            $table->integer('total_sessions_cancelled')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);

            // Performance Metrics
            $table->decimal('overall_grade', 5, 2)->nullable();
            $table->decimal('participation_score', 5, 2)->default(0);
            $table->decimal('homework_completion_rate', 5, 2)->default(0);

            // Assignment Statistics
            $table->integer('total_assignments_given')->default(0);
            $table->integer('total_assignments_completed')->default(0);
            $table->integer('pending_assignments')->default(0);
            $table->integer('overdue_assignments')->default(0);
            $table->date('last_assignment_submitted')->nullable();
            $table->date('next_assignment_due')->nullable();

            // Quiz Statistics
            $table->integer('total_quizzes_taken')->default(0);
            $table->decimal('average_quiz_score', 5, 2)->default(0);

            // Curriculum Tracking (JSON)
            $table->json('learning_objectives')->nullable();
            $table->json('completed_topics')->nullable();
            $table->json('current_topics')->nullable();
            $table->json('upcoming_topics')->nullable();
            $table->text('curriculum_notes')->nullable();

            // Assessment (JSON)
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('improvement_areas')->nullable();
            $table->text('learning_style_notes')->nullable();

            // Feedback
            $table->text('teacher_feedback')->nullable();
            $table->text('student_feedback')->nullable();
            $table->text('parent_feedback')->nullable();

            // Reports (JSON)
            $table->json('monthly_reports')->nullable();
            $table->timestamp('last_report_generated')->nullable();

            // Attendance Tracking
            $table->integer('consecutive_attended_sessions')->default(0);
            $table->integer('consecutive_missed_sessions')->default(0);
            $table->timestamp('last_attendance_update')->nullable();

            // Communication Tracking
            $table->timestamp('last_teacher_contact')->nullable();
            $table->timestamp('last_student_contact')->nullable();
            $table->timestamp('last_parent_contact')->nullable();
            $table->text('communication_notes')->nullable();

            // Engagement Metrics
            $table->enum('engagement_level', ['excellent', 'good', 'average', 'below_average', 'poor'])->nullable();
            $table->enum('motivation_level', ['very_high', 'high', 'medium', 'low', 'very_low'])->nullable();
            $table->text('behavioral_notes')->nullable();
            $table->text('special_needs_notes')->nullable();

            // Goals and Milestones (JSON)
            $table->json('short_term_goals')->nullable();
            $table->json('long_term_goals')->nullable();
            $table->json('achieved_milestones')->nullable();
            $table->json('upcoming_milestones')->nullable();

            // Recommendations
            $table->text('teacher_recommendations')->nullable();
            $table->json('recommended_resources')->nullable();
            $table->json('intervention_strategies')->nullable();
            $table->boolean('needs_additional_support')->default(false);

            // Status
            $table->enum('progress_status', ['excellent', 'good', 'satisfactory', 'needs_improvement', 'concerning'])->default('satisfactory');
            $table->boolean('is_active')->default(true);

            // Admin Notes
            $table->text('admin_notes')->nullable();

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes for performance
            $table->index(['academy_id', 'student_id']);
            $table->index(['subscription_id']);
            $table->index(['teacher_id']);
            $table->index(['progress_status']);
            $table->index(['is_active']);
            $table->index(['needs_additional_support']);
            $table->index(['attendance_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_progresses');
    }
};
