<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'interactive' to the session_type ENUM for InteractiveCourseSession support
     */
    public function up(): void
    {
        // MySQL requires raw SQL to modify ENUM values
        DB::statement("ALTER TABLE meeting_attendances MODIFY COLUMN session_type ENUM('individual', 'group', 'academic', 'interactive') DEFAULT 'individual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'interactive' from ENUM (only if no records use it)
        DB::statement("ALTER TABLE meeting_attendances MODIFY COLUMN session_type ENUM('individual', 'group', 'academic') DEFAULT 'individual'");
    }
};
