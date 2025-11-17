<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove duplicate meeting fields from quran_trial_requests table.
     * These fields are now handled by QuranSession and BaseSessionMeeting
     * for consistent meeting management across all session types.
     */
    public function up(): void
    {
        Schema::table('quran_trial_requests', function (Blueprint $table) {
            // Remove duplicate meeting management fields
            // Meeting info is now stored in base_session_meetings via QuranSession
            $table->dropColumn([
                'scheduled_at',      // Now in QuranSession.scheduled_at
                'meeting_link',      // Now in BaseSessionMeeting.room_url
                'meeting_password',  // Not needed for LiveKit
            ]);

            // Keep only the fields specific to trial request management:
            // - request metadata (student info, preferences)
            // - status tracking
            // - relationship to created session (trial_session_id)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_trial_requests', function (Blueprint $table) {
            // Restore columns if rollback is needed
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->string('meeting_link')->nullable()->after('scheduled_at');
            $table->string('meeting_password')->nullable()->after('meeting_link');
        });
    }
};
