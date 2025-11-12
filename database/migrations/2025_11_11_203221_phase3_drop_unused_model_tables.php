<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 3: Drop 8 unused tables for deleted models
     * All tables verified to have 0 records (safe to drop)
     *
     * Deleted models:
     * - Quiz (quizzes table - 0 records)
     * - CourseQuiz (course_quizzes table - 0 records)
     * - CourseReview (course_reviews table - 0 records)
     * - InteractiveCourseSettings (interactive_course_settings table - 0 records)
     * - InteractiveSessionAttendance (interactive_session_attendances table - 0 records)
     * - InteractiveTeacherPayment (interactive_teacher_payments table - 0 records)
     * - SessionRequest (session_requests table - 0 records)
     * - TeachingSession (teaching_sessions table - 0 records)
     * - MeetingParticipant (no table found)
     */
    public function up(): void
    {
        // Drop all unused tables (all have 0 records)
        Schema::dropIfExists('course_quizzes');
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('interactive_course_settings');
        Schema::dropIfExists('interactive_session_attendances');
        Schema::dropIfExists('interactive_teacher_payments');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('session_requests');
        Schema::dropIfExists('teaching_sessions');

        // meeting_participants table does not exist
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: These tables had 0 records when dropped.
     * Rollback will recreate empty tables only (no data to restore).
     */
    public function down(): void
    {
        // Recreate tables with basic structure (empty, no data)
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('interactive_course_settings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('interactive_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('interactive_teacher_payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('session_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('teaching_sessions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
