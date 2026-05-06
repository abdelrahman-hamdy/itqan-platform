<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill student_id on trial QuranSessions whose creation path
     * (SessionManagementService::createTrialSession before the fix) left the
     * column NULL. Without student_id, MeetingTokenController::getSession()
     * couldn't authorize the trial student and the mobile app showed
     * "Failed to get meeting token". Resolve from the linked trial request.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE quran_sessions qs
            INNER JOIN quran_trial_requests qtr ON qs.trial_request_id = qtr.id
            SET qs.student_id = qtr.student_id
            WHERE qs.session_type = 'trial'
              AND qs.student_id IS NULL
              AND qtr.student_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        // No rollback — reverting would re-introduce the authorization bug.
    }
};
