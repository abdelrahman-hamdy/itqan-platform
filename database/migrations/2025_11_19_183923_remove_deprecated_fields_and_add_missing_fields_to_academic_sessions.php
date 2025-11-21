<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes 22 deprecated fields from academic_sessions table
     * and adds 3 missing fields to align with QuranSession structure.
     */
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign('academic_sessions_interactive_course_session_id_foreign');
            $table->dropForeign('academic_sessions_attendance_marked_by_foreign');

            // Drop indexes that use deprecated columns
            $table->dropIndex('academic_sessions_is_scheduled_scheduled_at_index');

            // Remove 22 deprecated fields
            $table->dropColumn([
                // 1. Wrong relationship (InteractiveCourseSession is separate)
                'interactive_course_session_id',

                // 2-5. Unnecessary flags
                'session_sequence',
                'is_template',
                'is_generated',
                'is_scheduled',

                // 6-10. Google Calendar/Meet fields (using LiveKit now)
                'google_event_id',
                'google_calendar_id',
                'google_meet_url',
                'google_meet_id',
                'google_attendees',

                // 11-13. Attendance fields (should be in AcademicSessionReport)
                'attendance_log',
                'attendance_marked_at',
                'attendance_marked_by',

                // 14. Session grade (should be in AcademicSessionReport)
                'session_grade',

                // 15-16. Notification fields (not needed)
                'notification_log',
                'reminder_sent_at',

                // 17-19. Error tracking (not needed)
                'meeting_creation_error',
                'last_error_at',
                'retry_count',

                // 20-21. Duplicate fields (already have from BaseSession)
                'cancellation_type',
                'rescheduling_note',

                // 22. Unnecessary flag
                'is_auto_generated',
            ]);
        });

        // Add missing fields in a separate statement to avoid issues
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Add 3 missing fields to align with QuranSession
            $table->boolean('subscription_counted')->default(false)->after('is_makeup_session')
                ->comment('Track if this session was counted towards subscription');

            $table->string('recording_url')->nullable()->after('homework_file')
                ->comment('URL to session recording if available');

            $table->boolean('recording_enabled')->default(false)->after('recording_url')
                ->comment('Whether recording is enabled for this session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Remove the added fields first
            $table->dropColumn([
                'subscription_counted',
                'recording_url',
                'recording_enabled',
            ]);
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            // Restore the removed fields
            $table->unsignedBigInteger('interactive_course_session_id')->nullable()->after('academic_individual_lesson_id');
            $table->integer('session_sequence')->default(0)->after('session_code');
            $table->boolean('is_template')->default(false)->after('session_type');
            $table->boolean('is_generated')->default(false)->after('is_template');
            $table->boolean('is_scheduled')->default(false)->after('status');
            $table->string('google_event_id')->nullable()->after('meeting_password');
            $table->string('google_calendar_id')->nullable()->after('google_event_id');
            $table->text('google_meet_url')->nullable()->after('google_calendar_id');
            $table->string('google_meet_id')->nullable()->after('google_meet_url');
            $table->json('google_attendees')->nullable()->after('google_meet_id');
            $table->json('attendance_log')->nullable()->after('attendance_notes');
            $table->timestamp('attendance_marked_at')->nullable()->after('attendance_log');
            $table->unsignedBigInteger('attendance_marked_by')->nullable()->after('attendance_marked_at');
            $table->decimal('session_grade', 3, 1)->nullable()->after('homework_file');
            $table->json('notification_log')->nullable()->after('follow_up_notes');
            $table->timestamp('reminder_sent_at')->nullable()->after('notification_log');
            $table->text('meeting_creation_error')->nullable()->after('reminder_sent_at');
            $table->timestamp('last_error_at')->nullable()->after('meeting_creation_error');
            $table->integer('retry_count')->default(0)->after('last_error_at');
            $table->string('cancellation_type')->nullable()->after('is_makeup_session');
            $table->text('rescheduling_note')->nullable()->after('rescheduled_to');
            $table->boolean('is_auto_generated')->default(false)->after('is_makeup_session');

            // Restore foreign keys
            $table->foreign('interactive_course_session_id')
                ->references('id')
                ->on('interactive_course_sessions')
                ->onDelete('cascade');

            $table->foreign('attendance_marked_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Restore index
            $table->index(['is_scheduled', 'scheduled_at']);
        });
    }
};
