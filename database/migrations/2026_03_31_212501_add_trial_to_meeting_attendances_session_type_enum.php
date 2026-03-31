<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'trial' to the meeting_attendances.session_type enum.
     *
     * Trial sessions were causing "Data truncated for column 'session_type'"
     * errors because the enum only allowed: individual, group, academic, interactive.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE meeting_attendances MODIFY COLUMN session_type ENUM('individual','group','academic','interactive','trial') NOT NULL DEFAULT 'individual'");
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE meeting_attendances MODIFY COLUMN session_type ENUM('individual','group','academic','interactive') NOT NULL DEFAULT 'individual'");
    }
};
