<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to drop Progress tables as part of session system simplification.
 *
 * Progress data is now calculated dynamically from session reports using:
 * - QuranReportService for Quran sessions
 * - AcademicReportService for Academic sessions
 * - InteractiveReportService for Interactive course sessions
 *
 * NOTE: student_progress table is NOT dropped - it's used for recorded courses.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop interactive_course_progress first (has foreign keys)
        Schema::dropIfExists('interactive_course_progress');

        // Drop quran_progress (has foreign keys)
        Schema::dropIfExists('quran_progress');

        // Drop academic_progress
        Schema::dropIfExists('academic_progress');
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: This migration is intentionally not reversible.
     * Progress data is now calculated dynamically from session reports.
     * If you need to restore these tables, recreate them manually and
     * backfill data from session reports.
     */
    public function down(): void
    {
        // Intentionally left empty - progress tables are deprecated
        // and should not be recreated
    }
};
