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
        Schema::create('academic_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('subscription_id'); // Link to AcademicSubscription
            $table->unsignedBigInteger('student_id'); // User ID of the student
            $table->unsignedBigInteger('teacher_id'); // AcademicTeacher ID
            $table->unsignedBigInteger('subject_id'); // Subject ID
            
            // Progress tracking
            $table->string('progress_code')->unique(); // PROG-{academy_id}-{sequential}
            $table->date('start_date'); // When progress tracking started
            $table->date('last_session_date')->nullable(); // Last completed session
            $table->date('next_session_date')->nullable(); // Next scheduled session
            
            // Overall progress metrics
            $table->integer('total_sessions_planned')->default(0);
            $table->integer('total_sessions_completed')->default(0);
            $table->integer('total_sessions_missed')->default(0);
            $table->integer('total_sessions_cancelled')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0); // Percentage
            
            // Academic performance
            $table->decimal('overall_grade', 5, 2)->nullable(); // Out of 100
            $table->decimal('participation_score', 5, 2)->default(0); // Out of 100
            $table->decimal('homework_completion_rate', 5, 2)->default(0); // Percentage
            $table->integer('total_assignments_given')->default(0);
            $table->integer('total_assignments_completed')->default(0);
            $table->integer('total_quizzes_taken')->default(0);
            $table->decimal('average_quiz_score', 5, 2)->default(0); // Average score
            
            // Learning objectives and curriculum
            $table->json('learning_objectives')->nullable(); // Set at start of course
            $table->json('completed_topics')->nullable(); // Topics/chapters completed
            $table->json('current_topics')->nullable(); // Currently studying
            $table->json('upcoming_topics')->nullable(); // Planned topics
            $table->text('curriculum_notes')->nullable(); // Teacher's curriculum notes
            
            // Student strengths and weaknesses
            $table->json('strengths')->nullable(); // Student's strong areas
            $table->json('weaknesses')->nullable(); // Areas needing improvement
            $table->json('improvement_areas')->nullable(); // Specific areas to focus on
            $table->text('learning_style_notes')->nullable(); // How student learns best
            
            // Progress reports and feedback
            $table->text('teacher_feedback')->nullable(); // Latest teacher feedback
            $table->text('student_feedback')->nullable(); // Latest student feedback
            $table->text('parent_feedback')->nullable(); // Parent feedback (if applicable)
            $table->json('monthly_reports')->nullable(); // Generated monthly reports
            $table->timestamp('last_report_generated')->nullable();
            
            // Session tracking
            $table->integer('consecutive_attended_sessions')->default(0);
            $table->integer('consecutive_missed_sessions')->default(0);
            $table->timestamp('last_attendance_update')->nullable();
            
            // Homework and assignments tracking
            $table->integer('pending_assignments')->default(0);
            $table->integer('overdue_assignments')->default(0);
            $table->date('last_assignment_submitted')->nullable();
            $table->date('next_assignment_due')->nullable();
            
            // Communication log
            $table->timestamp('last_teacher_contact')->nullable();
            $table->timestamp('last_student_contact')->nullable();
            $table->timestamp('last_parent_contact')->nullable();
            $table->text('communication_notes')->nullable();
            
            // Behavioral and engagement notes
            $table->enum('engagement_level', ['excellent', 'good', 'average', 'below_average', 'poor'])->nullable();
            $table->enum('motivation_level', ['very_high', 'high', 'medium', 'low', 'very_low'])->nullable();
            $table->text('behavioral_notes')->nullable();
            $table->text('special_needs_notes')->nullable();
            
            // Goals and milestones
            $table->json('short_term_goals')->nullable(); // Goals for next month
            $table->json('long_term_goals')->nullable(); // Goals for semester/year
            $table->json('achieved_milestones')->nullable(); // Completed milestones
            $table->json('upcoming_milestones')->nullable(); // Upcoming milestones
            
            // Recommendations and interventions
            $table->text('teacher_recommendations')->nullable();
            $table->json('recommended_resources')->nullable(); // Books, videos, etc.
            $table->json('intervention_strategies')->nullable(); // If student struggling
            $table->boolean('needs_additional_support')->default(false);
            
            // Progress status
            $table->enum('progress_status', [
                'excellent', // Exceeding expectations
                'good', // Meeting expectations
                'satisfactory', // Making adequate progress
                'needs_improvement', // Below expectations
                'concerning' // Significant issues
            ])->default('satisfactory');
            
            // Administrative
            $table->boolean('is_active')->default(true);
            $table->text('admin_notes')->nullable(); // Administrative notes
            $table->unsignedBigInteger('created_by')->nullable(); // Who created this record
            $table->unsignedBigInteger('updated_by')->nullable(); // Who last updated
            
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'is_active']);
            $table->index(['subscription_id']);
            $table->index(['student_id', 'subject_id']);
            $table->index(['teacher_id', 'progress_status']);
            $table->index(['progress_status', 'is_active']);
            $table->index(['last_session_date', 'next_session_date']);
            $table->index('progress_code');
            $table->index(['attendance_rate', 'overall_grade']);

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_progress');
    }
};
