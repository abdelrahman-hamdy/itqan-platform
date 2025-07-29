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
        Schema::create('quran_homework', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('quran_subscriptions')->nullOnDelete();
            $table->foreignId('circle_id')->nullable()->constrained('quran_circles')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('quran_sessions')->nullOnDelete();
            
            // Basic Information
            $table->string('homework_code', 50)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('homework_type', ['memorization', 'recitation', 'review', 'research', 'writing', 'listening', 'practice'])->default('memorization');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('difficulty_level', ['very_easy', 'easy', 'medium', 'hard', 'very_hard'])->default('medium');
            
            // Assignment Details
            $table->integer('estimated_duration_minutes')->nullable();
            $table->text('instructions')->nullable();
            $table->json('requirements')->nullable();
            $table->json('learning_objectives')->nullable();
            
            // Quran Assignment Specifics
            $table->integer('surah_assignment')->nullable();
            $table->integer('verse_from')->nullable();
            $table->integer('verse_to')->nullable();
            $table->integer('total_verses')->default(0);
            
            // Assignment Requirements
            $table->boolean('memorization_required')->default(false);
            $table->boolean('recitation_required')->default(false);
            $table->json('tajweed_focus_areas')->nullable();
            $table->text('pronunciation_notes')->nullable();
            $table->integer('repetition_count_required')->default(0);
            
            // Submission Requirements
            $table->boolean('audio_submission_required')->default(false);
            $table->boolean('video_submission_required')->default(false);
            $table->boolean('written_submission_required')->default(false);
            
            // Materials and Resources
            $table->json('practice_materials')->nullable();
            $table->json('reference_materials')->nullable();
            
            // Timing
            $table->timestamp('assigned_at');
            $table->timestamp('due_date');
            $table->timestamp('reminder_sent_at')->nullable();
            
            // Submission Details
            $table->enum('submission_method', ['audio', 'video', 'text', 'file', 'live', 'mixed'])->nullable();
            $table->text('submission_text')->nullable();
            $table->json('submission_files')->nullable();
            $table->string('audio_recording_url')->nullable();
            $table->string('video_recording_url')->nullable();
            $table->text('submission_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('submission_status', ['not_submitted', 'partial', 'complete', 'late', 'resubmission'])->default('not_submitted');
            
            // Evaluation
            $table->json('evaluation_criteria')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->decimal('grade', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('quality_score', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('accuracy_score', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('effort_score', 3, 1)->nullable()->comment('1-10 scale');
            
            // Performance Analysis
            $table->json('improvement_areas')->nullable();
            $table->json('strengths_noted')->nullable();
            $table->json('next_steps')->nullable();
            
            // Follow-up
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            
            // Status Management
            $table->enum('status', ['assigned', 'in_progress', 'submitted', 'evaluated', 'completed', 'overdue', 'cancelled'])->default('assigned');
            $table->decimal('completion_percentage', 5, 2)->default(0);
            
            // Time Tracking
            $table->integer('time_spent_minutes')->default(0);
            $table->integer('attempts_count')->default(0);
            
            // Parent Involvement
            $table->boolean('parent_reviewed')->default(false);
            $table->text('parent_feedback')->nullable();
            $table->boolean('parent_signature')->default(false);
            
            // Extensions and Late Submissions
            $table->boolean('extension_requested')->default(false);
            $table->boolean('extension_granted')->default(false);
            $table->timestamp('new_due_date')->nullable();
            $table->text('extension_reason')->nullable();
            $table->boolean('late_submission')->default(false);
            $table->boolean('late_penalty_applied')->default(false);
            
            // Scoring
            $table->decimal('bonus_points', 3, 1)->default(0);
            $table->decimal('total_score', 3, 1)->nullable();
            
            // Administrative
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'due_date']);
            $table->index(['academy_id', 'status']);
            $table->index(['student_id', 'due_date']);
            $table->index(['student_id', 'status']);
            $table->index(['quran_teacher_id', 'due_date']);
            $table->index(['quran_teacher_id', 'status']);
            $table->index(['subscription_id', 'assigned_at']);
            $table->index(['circle_id', 'assigned_at']);
            $table->index(['session_id', 'homework_type']);
            $table->index(['homework_type', 'priority']);
            $table->index(['status', 'due_date']);
            $table->index(['submission_status', 'submitted_at']);
            $table->index(['follow_up_required', 'evaluated_at']);
            $table->index(['parent_reviewed', 'status']);
            $table->index(['late_submission', 'due_date']);
            $table->index('homework_code');
            
            // Unique Constraints
            $table->unique(['academy_id', 'homework_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_homework');
    }
};
