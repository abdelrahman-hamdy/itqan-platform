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
        Schema::create('interactive_session_reports', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('session_id')->constrained('interactive_course_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academy_id')->nullable()->constrained('academies')->nullOnDelete();

            // Shared report fields (from BaseSessionReport)
            $table->text('notes')->nullable();
            $table->timestamp('meeting_enter_time')->nullable();
            $table->timestamp('meeting_leave_time')->nullable();
            $table->integer('actual_attendance_minutes')->default(0);
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->string('attendance_status')->default('absent');
            $table->decimal('attendance_percentage', 5, 2)->default(0);
            $table->integer('connection_quality_score')->nullable();
            $table->json('meeting_events')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->boolean('is_auto_calculated')->default(true);
            $table->boolean('manually_overridden')->default(false);
            $table->text('override_reason')->nullable();

            // Interactive-specific fields
            $table->decimal('quiz_score', 5, 2)->nullable();
            $table->decimal('video_completion_percentage', 5, 2)->default(0);
            $table->integer('exercises_completed')->default(0);
            $table->decimal('engagement_score', 3, 1)->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['session_id', 'student_id']);
            $table->index('attendance_status');
            $table->index('evaluated_at');
            $table->index('academy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactive_session_reports');
    }
};
