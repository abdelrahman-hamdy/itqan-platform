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
        // ==========================================
        // UPDATE ACADEMIC SESSION REPORTS
        // ==========================================

        // Step 1: Rename columns for consistency
        Schema::table('academic_session_reports', function (Blueprint $table) {
            $table->renameColumn('manually_overridden', 'manually_evaluated');
            $table->renameColumn('is_auto_calculated', 'is_calculated');
        });

        // Step 2: Update attendance_status enum values
        DB::statement("ALTER TABLE academic_session_reports ADD COLUMN attendance_status_new ENUM('attended', 'late', 'leaved', 'absent') DEFAULT 'absent'");

        DB::statement("
            UPDATE academic_session_reports
            SET attendance_status_new = CASE
                WHEN attendance_status = 'present' THEN 'attended'
                WHEN attendance_status = 'partial' THEN 'leaved'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        DB::statement("ALTER TABLE academic_session_reports DROP COLUMN attendance_status");
        DB::statement("ALTER TABLE academic_session_reports CHANGE attendance_status_new attendance_status ENUM('attended', 'late', 'leaved', 'absent') DEFAULT 'absent' NOT NULL");

        // ==========================================
        // UPDATE INTERACTIVE SESSION REPORTS
        // ==========================================

        // Step 1: Rename columns for consistency
        Schema::table('interactive_session_reports', function (Blueprint $table) {
            $table->renameColumn('manually_overridden', 'manually_evaluated');
            $table->renameColumn('is_auto_calculated', 'is_calculated');
        });

        // Step 2: Update attendance_status values (it's already a string, so just update data)
        DB::statement("
            UPDATE interactive_session_reports
            SET attendance_status = CASE
                WHEN attendance_status = 'present' THEN 'attended'
                WHEN attendance_status = 'partial' THEN 'leaved'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ==========================================
        // REVERT INTERACTIVE SESSION REPORTS
        // ==========================================

        DB::statement("
            UPDATE interactive_session_reports
            SET attendance_status = CASE
                WHEN attendance_status = 'attended' THEN 'present'
                WHEN attendance_status = 'leaved' THEN 'partial'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        Schema::table('interactive_session_reports', function (Blueprint $table) {
            $table->renameColumn('manually_evaluated', 'manually_overridden');
            $table->renameColumn('is_calculated', 'is_auto_calculated');
        });

        // ==========================================
        // REVERT ACADEMIC SESSION REPORTS
        // ==========================================

        DB::statement("ALTER TABLE academic_session_reports ADD COLUMN attendance_status_old ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent'");

        DB::statement("
            UPDATE academic_session_reports
            SET attendance_status_old = CASE
                WHEN attendance_status = 'attended' THEN 'present'
                WHEN attendance_status = 'leaved' THEN 'partial'
                WHEN attendance_status = 'late' THEN 'late'
                WHEN attendance_status = 'absent' THEN 'absent'
                ELSE 'absent'
            END
        ");

        DB::statement("ALTER TABLE academic_session_reports DROP COLUMN attendance_status");
        DB::statement("ALTER TABLE academic_session_reports CHANGE attendance_status_old attendance_status ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent' NOT NULL");

        Schema::table('academic_session_reports', function (Blueprint $table) {
            $table->renameColumn('manually_evaluated', 'manually_overridden');
            $table->renameColumn('is_calculated', 'is_auto_calculated');
        });
    }
};
