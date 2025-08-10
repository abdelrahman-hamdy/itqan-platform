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
        Schema::table('users', function (Blueprint $table) {
            // Teacher Meeting Preferences
            $table->boolean('teacher_auto_record')->default(false)->after('google_calendar_enabled');
            $table->integer('teacher_default_duration')->default(60)->after('teacher_auto_record'); // minutes
            $table->integer('teacher_meeting_prep_minutes')->default(60)->after('teacher_default_duration'); // minutes
            $table->boolean('teacher_send_reminders')->default(true)->after('teacher_meeting_prep_minutes');
            $table->json('teacher_reminder_times')->nullable()->after('teacher_send_reminders'); // [60, 15]
            
            // Calendar Settings  
            $table->boolean('sync_to_google_calendar')->default(true)->after('teacher_reminder_times');
            $table->boolean('allow_calendar_conflicts')->default(false)->after('sync_to_google_calendar');
            $table->string('calendar_visibility')->default('default')->after('allow_calendar_conflicts'); // default, public, private
            
            // Notification Settings
            $table->boolean('notify_on_student_join')->default(true)->after('calendar_visibility');
            $table->boolean('notify_on_session_end')->default(false)->after('notify_on_student_join');
            $table->string('notification_method')->default('both')->after('notify_on_session_end'); // email, platform, both
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_auto_record',
                'teacher_default_duration',
                'teacher_meeting_prep_minutes',
                'teacher_send_reminders',
                'teacher_reminder_times',
                'sync_to_google_calendar',
                'allow_calendar_conflicts',
                'calendar_visibility',
                'notify_on_student_join',
                'notify_on_session_end',
                'notification_method',
            ]);
        });
    }
}; 