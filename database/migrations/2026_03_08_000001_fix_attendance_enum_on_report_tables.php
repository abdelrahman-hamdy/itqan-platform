<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix attendance_status enum on student_session_reports and academic_session_reports.
     *
     * The 2026_02_15 migration fixed meeting_attendances but missed these two tables,
     * leaving them with 'leaved' instead of 'left'. This causes SQL truncation errors
     * when saving AttendanceStatus::LEFT ('left') from PHP.
     */
    public function up(): void
    {
        $tables = ['student_session_reports', 'academic_session_reports'];

        foreach ($tables as $table) {
            // Step 1: Expand enum to include both old and new values
            DB::statement("ALTER TABLE `{$table}`
                MODIFY `attendance_status` ENUM('attended','late','left','leaved','absent') NULL DEFAULT NULL");

            // Step 2: Migrate existing data
            DB::table($table)
                ->where('attendance_status', 'leaved')
                ->update(['attendance_status' => 'left']);

            // Step 3: Shrink enum to only valid PHP values
            DB::statement("ALTER TABLE `{$table}`
                MODIFY `attendance_status` ENUM('attended','late','left','absent') NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        $tables = ['student_session_reports', 'academic_session_reports'];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE `{$table}`
                MODIFY `attendance_status` ENUM('attended','late','left','leaved','absent') NULL DEFAULT NULL");

            DB::table($table)
                ->where('attendance_status', 'left')
                ->update(['attendance_status' => 'leaved']);

            DB::statement("ALTER TABLE `{$table}`
                MODIFY `attendance_status` ENUM('attended','late','leaved','absent') NULL DEFAULT NULL");
        }
    }
};
