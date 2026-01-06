<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfills academy_id on interactive_course_sessions from the course relationship.
     * This ensures the academy() relationship works properly with eager loading.
     */
    public function up(): void
    {
        // Update all sessions where academy_id is NULL using the course's academy_id
        DB::statement('
            UPDATE interactive_course_sessions AS s
            INNER JOIN interactive_courses AS c ON s.course_id = c.id
            SET s.academy_id = c.academy_id
            WHERE s.academy_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * We don't need to reverse this - the data was correct, just missing.
     */
    public function down(): void
    {
        // Intentionally empty - no need to set academy_id back to NULL
    }
};
