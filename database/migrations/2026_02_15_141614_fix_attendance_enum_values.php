<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix PHP Enum ↔ MySQL Enum mismatch that caused ALL webhook attendance data
     * to silently fail insertion.
     *
     * meeting_attendance_events.event_type:
     *   DB had: enum('join','leave','reconnect','aborted')
     *   PHP has: MeetingEventType::JOINED='joined', LEFT='left'
     *   Fix: align DB to enum('joined','left','reconnect','aborted')
     *
     * meeting_attendances.attendance_status:
     *   DB had: enum('attended','late','leaved','absent')
     *   PHP has: AttendanceStatus::LEFT='left'
     *   Fix: align DB to enum('attended','late','left','absent')
     */
    public function up(): void
    {
        // Step 1: Update existing data before altering enum (to prevent data loss)
        DB::table('meeting_attendance_events')
            ->where('event_type', 'join')
            ->update(['event_type' => DB::raw("'join'")]);

        DB::table('meeting_attendance_events')
            ->where('event_type', 'leave')
            ->update(['event_type' => DB::raw("'leave'")]);

        DB::table('meeting_attendances')
            ->where('attendance_status', 'leaved')
            ->update(['attendance_status' => DB::raw("'leaved'")]);

        // Step 2: Expand enums to include both old and new values (transitional)
        DB::statement("ALTER TABLE meeting_attendance_events
            MODIFY event_type ENUM('join','joined','leave','left','reconnect','aborted') NOT NULL DEFAULT 'joined'");

        DB::statement("ALTER TABLE meeting_attendances
            MODIFY attendance_status ENUM('attended','late','left','leaved','absent') NOT NULL DEFAULT 'absent'");

        // Step 3: Migrate old values to new values
        DB::table('meeting_attendance_events')
            ->where('event_type', 'join')
            ->update(['event_type' => 'joined']);

        DB::table('meeting_attendance_events')
            ->where('event_type', 'leave')
            ->update(['event_type' => 'left']);

        DB::table('meeting_attendances')
            ->where('attendance_status', 'leaved')
            ->update(['attendance_status' => 'left']);

        // Step 4: Shrink enums to only valid PHP values
        DB::statement("ALTER TABLE meeting_attendance_events
            MODIFY event_type ENUM('joined','left','reconnect','aborted') NOT NULL DEFAULT 'joined'");

        DB::statement("ALTER TABLE meeting_attendances
            MODIFY attendance_status ENUM('attended','late','left','absent') NOT NULL DEFAULT 'absent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Expand to include both
        DB::statement("ALTER TABLE meeting_attendance_events
            MODIFY event_type ENUM('join','joined','leave','left','reconnect','aborted') NOT NULL DEFAULT 'join'");

        DB::statement("ALTER TABLE meeting_attendances
            MODIFY attendance_status ENUM('attended','late','left','leaved','absent') NOT NULL DEFAULT 'absent'");

        // Migrate back
        DB::table('meeting_attendance_events')
            ->where('event_type', 'joined')
            ->update(['event_type' => 'join']);

        DB::table('meeting_attendance_events')
            ->where('event_type', 'left')
            ->update(['event_type' => 'leave']);

        DB::table('meeting_attendances')
            ->where('attendance_status', 'left')
            ->update(['attendance_status' => 'leaved']);

        // Shrink to original
        DB::statement("ALTER TABLE meeting_attendance_events
            MODIFY event_type ENUM('join','leave','reconnect','aborted') NOT NULL DEFAULT 'join'");

        DB::statement("ALTER TABLE meeting_attendances
            MODIFY attendance_status ENUM('attended','late','leaved','absent') NOT NULL DEFAULT 'absent'");
    }
};
