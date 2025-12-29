<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates all 'leaved' attendance status values to 'left' across all relevant tables.
     * This corrects the grammatically incorrect enum value.
     */
    public function up(): void
    {
        // List of tables that have attendance_status column
        $tables = [
            'meeting_attendances',
            'student_session_reports',
            'academic_session_reports',
            'interactive_session_reports',
            'quran_session_reports',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) &&
                DB::getSchemaBuilder()->hasColumn($table, 'attendance_status')) {
                DB::table($table)
                    ->where('attendance_status', 'leaved')
                    ->update(['attendance_status' => 'left']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // List of tables that have attendance_status column
        $tables = [
            'meeting_attendances',
            'student_session_reports',
            'academic_session_reports',
            'interactive_session_reports',
            'quran_session_reports',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) &&
                DB::getSchemaBuilder()->hasColumn($table, 'attendance_status')) {
                DB::table($table)
                    ->where('attendance_status', 'left')
                    ->update(['attendance_status' => 'leaved']);
            }
        }
    }
};
