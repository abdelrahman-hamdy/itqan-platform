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
        // Step 1: Add temporary column with new enum values
        DB::statement("ALTER TABLE meeting_attendances ADD COLUMN attendance_status_new ENUM('attended', 'late', 'leaved', 'absent') DEFAULT 'absent'");

        // Step 2: Migrate existing data with mapping:
        // 'present' -> 'attended'
        // 'partial' -> 'leaved'
        // 'late' -> 'late' (unchanged)
        // 'absent' -> 'absent' (unchanged)
        DB::statement("
            UPDATE meeting_attendances
            SET attendance_status_new = CASE
                WHEN attendance_status = 'present' THEN 'attended'
                WHEN attendance_status = 'partial' THEN 'leaved'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        // Step 3: Drop old column
        DB::statement("ALTER TABLE meeting_attendances DROP COLUMN attendance_status");

        // Step 4: Rename temporary column to original name
        DB::statement("ALTER TABLE meeting_attendances CHANGE attendance_status_new attendance_status ENUM('attended', 'late', 'leaved', 'absent') DEFAULT 'absent' NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add temporary column with old enum values
        DB::statement("ALTER TABLE meeting_attendances ADD COLUMN attendance_status_old ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent'");

        // Step 2: Reverse mapping:
        // 'attended' -> 'present'
        // 'leaved' -> 'partial'
        // 'late' -> 'late' (unchanged)
        // 'absent' -> 'absent' (unchanged)
        DB::statement("
            UPDATE meeting_attendances
            SET attendance_status_old = CASE
                WHEN attendance_status = 'attended' THEN 'present'
                WHEN attendance_status = 'leaved' THEN 'partial'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        // Step 3: Drop new column
        DB::statement("ALTER TABLE meeting_attendances DROP COLUMN attendance_status");

        // Step 4: Rename temporary column back
        DB::statement("ALTER TABLE meeting_attendances CHANGE attendance_status_old attendance_status ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent' NOT NULL");
    }
};
