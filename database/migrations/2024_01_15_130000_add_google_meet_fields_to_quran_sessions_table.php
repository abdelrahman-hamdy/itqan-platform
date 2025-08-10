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
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Schedule relationship
            $table->foreignId('session_schedule_id')->nullable()->after('circle_id')
                  ->constrained()->nullOnDelete();
            
            // Google Calendar integration
            $table->string('google_event_id')->nullable()->after('meeting_password');
            $table->string('google_calendar_id')->nullable()->after('google_event_id');
            $table->text('google_meet_url')->nullable()->after('google_calendar_id');
            $table->string('google_meet_id')->nullable()->after('google_meet_url');
            $table->json('google_attendees')->nullable()->after('google_meet_id');
            
            // Meeting management
            $table->string('meeting_source')->default('platform')->after('google_attendees'); // 'google', 'platform', 'manual'
            $table->foreignId('created_by_user_id')->nullable()->after('meeting_source')
                  ->constrained('users')->nullOnDelete();
            $table->boolean('is_auto_generated')->default(false)->after('is_makeup_session');
            $table->timestamp('preparation_completed_at')->nullable()->after('ended_at');
            $table->timestamp('meeting_created_at')->nullable()->after('preparation_completed_at');
            
            // Attendance tracking
            $table->json('attendance_log')->nullable()->after('attendance_notes');
            $table->timestamp('attendance_marked_at')->nullable()->after('attendance_log');
            $table->foreignId('attendance_marked_by')->nullable()->after('attendance_marked_at')
                  ->constrained('users')->nullOnDelete();
            
            // Notification tracking
            $table->json('notification_log')->nullable()->after('follow_up_notes');
            $table->timestamp('reminder_sent_at')->nullable()->after('notification_log');
            
            // Error handling
            $table->text('meeting_creation_error')->nullable()->after('reminder_sent_at');
            $table->timestamp('last_error_at')->nullable()->after('meeting_creation_error');
            $table->integer('retry_count')->default(0)->after('last_error_at');
            
            // Indexes for performance
            $table->index(['session_schedule_id', 'status']);
            $table->index(['google_event_id']);
            $table->index(['is_auto_generated', 'scheduled_at']);
            $table->index(['preparation_completed_at']);
            $table->index(['meeting_source', 'academy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['session_schedule_id', 'status']);
            $table->dropIndex(['google_event_id']);
            $table->dropIndex(['is_auto_generated', 'scheduled_at']);
            $table->dropIndex(['preparation_completed_at']);
            $table->dropIndex(['meeting_source', 'academy_id']);
            
            // Drop columns
            $table->dropForeign(['session_schedule_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['attendance_marked_by']);
            
            $table->dropColumn([
                'session_schedule_id',
                'google_event_id',
                'google_calendar_id',
                'google_meet_url',
                'google_meet_id',
                'google_attendees',
                'meeting_source',
                'created_by_user_id',
                'is_auto_generated',
                'preparation_completed_at',
                'meeting_created_at',
                'attendance_log',
                'attendance_marked_at',
                'attendance_marked_by',
                'notification_log',
                'reminder_sent_at',
                'meeting_creation_error',
                'last_error_at',
                'retry_count'
            ]);
        });
    }
};