<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Rename columns for consistency
        Schema::table('student_session_reports', function (Blueprint $table) {
            $table->renameColumn('manually_overridden', 'manually_evaluated');
            $table->renameColumn('is_auto_calculated', 'is_calculated');
        });

        // Step 2: Update attendance_status enum values
        // Add temporary column with new enum values
        DB::statement("ALTER TABLE student_session_reports ADD COLUMN attendance_status_new ENUM('attended', 'late', 'leaved', 'absent') DEFAULT 'absent'");

        // Step 3: Migrate existing data with mapping:
        // 'present' -> 'attended'
        // 'partial' -> 'leaved'
        // 'late' -> 'late' (unchanged)
        // 'absent' -> 'absent' (unchanged)
        DB::statement("
            UPDATE student_session_reports
            SET attendance_status_new = CASE
                WHEN attendance_status = 'present' THEN 'attended'
                WHEN attendance_status = 'partial' THEN 'leaved'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        // Step 4: Drop old column
        DB::statement("ALTER TABLE student_session_reports DROP COLUMN attendance_status");

        // Step 5: Rename temporary column to original name
        DB::statement("ALTER TABLE student_session_reports CHANGE attendance_status_new attendance_status ENUM('attended', 'late', 'leaved', 'absent') DEFAULT 'absent' NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Revert enum values
        DB::statement("ALTER TABLE student_session_reports ADD COLUMN attendance_status_old ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent'");

        DB::statement("
            UPDATE student_session_reports
            SET attendance_status_old = CASE
                WHEN attendance_status = 'attended' THEN 'present'
                WHEN attendance_status = 'leaved' THEN 'partial'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        DB::statement("ALTER TABLE student_session_reports DROP COLUMN attendance_status");
        DB::statement("ALTER TABLE student_session_reports CHANGE attendance_status_old attendance_status ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent' NOT NULL");

        // Step 2: Rename columns back
        Schema::table('student_session_reports', function (Blueprint $table) {
            $table->renameColumn('manually_evaluated', 'manually_overridden');
            $table->renameColumn('is_calculated', 'is_auto_calculated');
        });
    }
};
