<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to update interactive_course_sessions status ENUM to match
 * quran_sessions and academic_sessions for consistency.
 *
 * Before: enum('scheduled','ongoing','completed','cancelled')
 * After:  enum('unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','missed','rescheduled')
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the ENUM to include all status values for consistency
        DB::statement("ALTER TABLE interactive_course_sessions MODIFY COLUMN status ENUM('unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','missed','rescheduled') NOT NULL DEFAULT 'scheduled'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any rows that have statuses not in the original ENUM
        // to a valid status before reverting
        DB::statement("UPDATE interactive_course_sessions SET status = 'scheduled' WHERE status IN ('unscheduled', 'ready', 'missed', 'rescheduled')");
        DB::statement("UPDATE interactive_course_sessions SET status = 'completed' WHERE status = 'absent'");

        // Revert to original ENUM
        DB::statement("ALTER TABLE interactive_course_sessions MODIFY COLUMN status ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled'");
    }
};
