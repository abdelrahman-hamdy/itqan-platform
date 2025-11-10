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
        Schema::create('academic_session_attendances', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            // Attendance status
            $table->enum('attendance_status', ['present', 'absent', 'late', 'partial', 'left_early'])->default('absent');

            // Manual tracking times
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();

            // Auto-tracking from LiveKit
            $table->timestamp('auto_join_time')->nullable();
            $table->timestamp('auto_leave_time')->nullable();
            $table->integer('auto_duration_minutes')->nullable();
            $table->boolean('auto_tracked')->default(false);

            // Manual override fields
            $table->boolean('manually_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('overridden_at')->nullable();
            $table->text('override_reason')->nullable();

            // Enhanced tracking
            $table->json('meeting_events')->nullable()->comment('JSON log of join/leave events from LiveKit');
            $table->integer('connection_quality_score')->nullable()->comment('1-10 scale');

            // Performance metrics
            $table->decimal('participation_score', 3, 1)->nullable()->comment('Participation score 0-10');
            $table->decimal('lesson_understanding', 3, 1)->nullable()->comment('Understanding level 0-10');
            $table->boolean('homework_completion')->default(false)->comment('Did student complete homework');
            $table->decimal('homework_quality', 3, 1)->nullable()->comment('Homework quality 0-10');
            $table->integer('questions_asked')->nullable()->comment('Number of questions asked');
            $table->integer('concepts_mastered')->nullable()->comment('Number of concepts mastered');

            // Teacher notes
            $table->text('notes')->nullable()->comment('Teacher notes and feedback');

            $table->timestamps();

            // Indexes for performance (with custom names to avoid length issues)
            $table->index(['session_id', 'student_id'], 'idx_acad_session_student');
            $table->index(['attendance_status'], 'idx_acad_attendance_status');
            $table->index(['join_time'], 'idx_acad_join_time');
            $table->index(['auto_tracked', 'manually_overridden'], 'idx_acad_tracking');
            $table->index(['session_id', 'auto_tracked'], 'idx_acad_session_tracking');
            $table->index('overridden_by', 'idx_acad_overridden_by');

            // Unique constraint to prevent duplicate attendance records
            $table->unique(['session_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_session_attendances');
    }
};
