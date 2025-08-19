<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'livekit' to meeting_source enum for quran_sessions
        DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN meeting_source ENUM('jitsi', 'whereby', 'custom', 'google', 'platform', 'manual', 'livekit') DEFAULT 'jitsi'");
        
        // Also update academic_sessions if it exists and has the column
        if (Schema::hasTable('academic_sessions') && Schema::hasColumn('academic_sessions', 'meeting_source')) {
            DB::statement("ALTER TABLE academic_sessions MODIFY COLUMN meeting_source ENUM('jitsi', 'whereby', 'custom', 'google', 'platform', 'manual', 'livekit') DEFAULT 'jitsi'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'livekit' from meeting_source enum for quran_sessions
        DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN meeting_source ENUM('jitsi', 'whereby', 'custom', 'google', 'platform', 'manual') DEFAULT 'jitsi'");
        
        // Also revert academic_sessions if it exists and has the column
        if (Schema::hasTable('academic_sessions') && Schema::hasColumn('academic_sessions', 'meeting_source')) {
            DB::statement("ALTER TABLE academic_sessions MODIFY COLUMN meeting_source ENUM('jitsi', 'whereby', 'custom', 'google', 'platform', 'manual') DEFAULT 'jitsi'");
        }
    }
};