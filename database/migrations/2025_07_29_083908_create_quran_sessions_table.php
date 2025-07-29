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
        Schema::create('quran_sessions', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('circle_id')->nullable()->constrained('quran_circles')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Session Details
            $table->string('session_code', 50)->unique();
            $table->enum('session_type', ['individual', 'circle', 'makeup', 'trial', 'assessment'])->default('individual');
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled', 'missed', 'rescheduled', 'pending'])->default('scheduled');
            
            // Basic Information
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->json('lesson_objectives')->nullable();
            
            // Timing
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(45);
            $table->integer('actual_duration_minutes')->nullable();
            
            // Location and Meeting
            $table->enum('location_type', ['online', 'physical', 'hybrid'])->default('online');
            $table->text('location_details')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id', 100)->nullable();
            $table->string('meeting_password', 50)->nullable();
            $table->string('recording_url')->nullable();
            $table->boolean('recording_enabled')->default(false);
            
            // Attendance
            $table->enum('attendance_status', ['attended', 'absent', 'late', 'left_early', 'partial'])->nullable();
            $table->integer('participants_count')->default(1);
            $table->text('attendance_notes')->nullable();
            
            // Quran Progress
            $table->integer('current_surah')->nullable();
            $table->integer('current_verse')->nullable();
            $table->integer('verses_covered_start')->nullable();
            $table->integer('verses_covered_end')->nullable();
            $table->integer('verses_memorized_today')->default(0);
            
            // Performance Assessment
            $table->decimal('recitation_quality', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('tajweed_accuracy', 3, 1)->nullable()->comment('1-10 scale');
            $table->integer('mistakes_count')->default(0);
            $table->json('common_mistakes')->nullable();
            $table->json('areas_for_improvement')->nullable();
            
            // Homework and Planning
            $table->json('homework_assigned')->nullable();
            $table->text('homework_details')->nullable();
            $table->text('next_session_plan')->nullable();
            
            // Feedback and Notes
            $table->text('session_notes')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->text('student_feedback')->nullable();
            $table->text('parent_feedback')->nullable();
            $table->integer('overall_rating')->nullable()->comment('1-5 rating');
            
            // Technical Issues
            $table->text('technical_issues')->nullable();
            
            // Makeup Session Management
            $table->foreignId('makeup_session_for')->nullable()->constrained('quran_sessions')->nullOnDelete();
            $table->boolean('is_makeup_session')->default(false);
            
            // Cancellation and Rescheduling
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamp('rescheduled_from')->nullable();
            $table->timestamp('rescheduled_to')->nullable();
            
            // Educational Content
            $table->json('materials_used')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->json('assessment_results')->nullable();
            
            // Follow-up
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();
            
            // Administrative
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'scheduled_at']);
            $table->index(['academy_id', 'status']);
            $table->index(['quran_teacher_id', 'scheduled_at']);
            $table->index(['quran_teacher_id', 'status']);
            $table->index(['student_id', 'scheduled_at']);
            $table->index(['quran_subscription_id', 'status']);
            $table->index(['circle_id', 'scheduled_at']);
            $table->index(['session_type', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['is_makeup_session', 'makeup_session_for']);
            $table->index(['attendance_status', 'scheduled_at']);
            $table->index('session_code');
            
            // Unique Constraints
            $table->unique(['academy_id', 'session_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_sessions');
    }
};
