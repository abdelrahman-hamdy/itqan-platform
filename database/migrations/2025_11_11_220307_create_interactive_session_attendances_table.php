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
        Schema::create('interactive_session_attendances', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('session_id')->constrained('interactive_course_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Shared attendance fields (from BaseSessionAttendance)
            $table->string('attendance_status')->default('absent');
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->timestamp('auto_join_time')->nullable();
            $table->timestamp('auto_leave_time')->nullable();
            $table->integer('auto_duration_minutes')->default(0);
            $table->boolean('auto_tracked')->default(false);
            $table->boolean('manually_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->text('override_reason')->nullable();
            $table->json('meeting_events')->nullable();
            $table->integer('connection_quality_score')->nullable();
            $table->decimal('participation_score', 3, 1)->nullable();
            $table->text('notes')->nullable();

            // Interactive-specific fields
            $table->decimal('video_completion_percentage', 5, 2)->default(0);
            $table->boolean('quiz_completion')->default(false);
            $table->integer('exercises_completed')->default(0);
            $table->decimal('interaction_score', 3, 1)->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['session_id', 'student_id']);
            $table->index('attendance_status');
            $table->index('auto_tracked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactive_session_attendances');
    }
};
