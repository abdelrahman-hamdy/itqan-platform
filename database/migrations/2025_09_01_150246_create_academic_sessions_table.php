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
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            
            // Basic relationships (adapted from Quran structure)
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_teacher_id')->constrained('academic_teacher_profiles')->onDelete('cascade');
            $table->foreignId('academic_subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('academic_individual_lesson_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('interactive_course_session_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Session identification (same as Quran)
            $table->string('session_code')->unique();
            $table->integer('session_sequence')->default(0);
            $table->enum('session_type', ['individual', 'interactive_course'])->default('individual');
            $table->boolean('is_template')->default(false);
            $table->boolean('is_generated')->default(false);
            
            // Session status and scheduling (same as Quran)
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('teacher_scheduled_at')->nullable();
            
            // Session details (same as Quran)
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('lesson_objectives')->nullable();
            
            // Timing (same as Quran)
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->integer('actual_duration_minutes')->nullable();
            
            // Meeting configuration (same as Quran, but no recording)
            $table->enum('location_type', ['online', 'physical', 'hybrid'])->default('online');
            $table->text('location_details')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('google_event_id')->nullable();
            $table->string('google_calendar_id')->nullable();
            $table->text('google_meet_url')->nullable();
            $table->string('google_meet_id')->nullable();
            $table->json('google_attendees')->nullable();
            $table->enum('meeting_source', ['manual', 'google', 'auto', 'livekit'])->default('auto');
            $table->string('meeting_platform')->nullable();
            $table->json('meeting_data')->nullable();
            $table->string('meeting_room_name')->nullable();
            $table->boolean('meeting_auto_generated')->default(true);
            $table->timestamp('meeting_expires_at')->nullable();
            
            // Attendance tracking (same as Quran)
            $table->enum('attendance_status', ['scheduled', 'present', 'absent', 'late', 'partial'])->default('scheduled');
            $table->integer('participants_count')->default(0);
            $table->text('attendance_notes')->nullable();
            $table->json('attendance_log')->nullable();
            $table->timestamp('attendance_marked_at')->nullable();
            $table->foreignId('attendance_marked_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Academic session content (replacing Quran fields)
            $table->text('session_topics_covered')->nullable();
            $table->text('lesson_content')->nullable();
            $table->json('learning_outcomes')->nullable();
            
            // Academic homework (your requirements)
            $table->text('homework_description')->nullable();
            $table->string('homework_file')->nullable(); // File upload path
            
            // Session evaluation (0-10 grade like Quran)
            $table->decimal('session_grade', 3, 1)->nullable(); // 0.0 to 10.0
            $table->text('session_notes')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->text('student_feedback')->nullable();
            $table->text('parent_feedback')->nullable();
            $table->integer('overall_rating')->nullable(); // 1-5 stars
            
            // Session management (same as Quran)
            $table->text('technical_issues')->nullable();
            $table->foreignId('makeup_session_for')->nullable()->constrained('academic_sessions')->onDelete('set null');
            $table->boolean('is_makeup_session')->default(false);
            $table->boolean('is_auto_generated')->default(false);
            
            // Cancellation and rescheduling (same as Quran)
            $table->text('cancellation_reason')->nullable();
            $table->string('cancellation_type')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamp('rescheduled_from')->nullable();
            $table->timestamp('rescheduled_to')->nullable();
            $table->text('rescheduling_note')->nullable();
            
            // Additional fields (same as Quran)
            $table->json('materials_used')->nullable();
            $table->json('assessment_results')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();
            
            // System fields (same as Quran)
            $table->json('notification_log')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->text('meeting_creation_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->integer('retry_count')->default(0);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['academy_id', 'scheduled_at']);
            $table->index(['academy_id', 'status']);
            $table->index(['academic_teacher_id', 'scheduled_at']);
            $table->index(['academic_teacher_id', 'status']);
            $table->index(['student_id', 'scheduled_at']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['session_type', 'status']);
            $table->index(['session_code']);
            $table->index(['is_scheduled', 'scheduled_at']);
            $table->index(['attendance_status', 'scheduled_at']);
            $table->unique(['academy_id', 'session_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};
