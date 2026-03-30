<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix 1: Change meeting_platform column default from 'jitsi' to 'livekit'
        $sessionTables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($sessionTables as $table) {
            if (Schema::hasColumn($table, 'meeting_platform')) {
                DB::statement("ALTER TABLE `{$table}` ALTER COLUMN `meeting_platform` SET DEFAULT 'livekit'");

                // Fix existing data
                DB::table($table)
                    ->where('meeting_platform', 'jitsi')
                    ->update(['meeting_platform' => 'livekit']);
            }
        }

        // Note: meeting_attendances.session_type uses the DB enum values
        // ('individual', 'group', 'academic', 'interactive').
        // The HasMeetings::meetingAttendances() relationship was fixed to match these values
        // instead of using getMeetingSessionType() which returns 'quran'.
    }

    public function down(): void
    {
        // Revert default back to 'jitsi'
        $sessionTables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($sessionTables as $table) {
            if (Schema::hasColumn($table, 'meeting_platform')) {
                DB::statement("ALTER TABLE `{$table}` ALTER COLUMN `meeting_platform` SET DEFAULT 'jitsi'");
            }
        }
    }
};
