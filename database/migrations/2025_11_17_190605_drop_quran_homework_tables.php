<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop unused homework tracking tables to simplify the system.
     * Homework is now tracked through quran_session_homeworks (assignment)
     * and graded through student_session_reports (oral evaluation).
     */
    public function up(): void
    {
        // Drop quran_homework_assignments table (unused per-student tracking)
        Schema::dropIfExists('quran_homework_assignments');

        // Drop quran_homework table (legacy unused table with 85+ fields)
        Schema::dropIfExists('quran_homework');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Not recreating tables as they were unused/legacy
        // If needed, restore from git history:
        // - 2025_08_30_033058_create_quran_homework_assignments_table.php
        // - Original quran_homework migration (if exists)
    }
};
