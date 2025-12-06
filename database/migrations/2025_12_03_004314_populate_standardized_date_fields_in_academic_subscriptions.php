<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Populate standardized date fields (starts_at, ends_at) from legacy fields (start_date, end_date)
     *
     * Background:
     * - QuranSubscription uses: starts_at, ends_at, billing_cycle, total_sessions
     * - AcademicSubscription historically used: start_date, end_date, total_sessions_scheduled
     * - BaseSubscription added standardized fields but old data wasn't migrated
     *
     * This migration:
     * 1. Copies start_date → starts_at
     * 2. Copies end_date → ends_at
     * 3. Ensures all academic subscriptions use same field names as Quran subscriptions
     */
    public function up(): void
    {
        // Populate starts_at from start_date where starts_at is NULL
        DB::table('academic_subscriptions')
            ->whereNotNull('start_date')
            ->whereNull('starts_at')
            ->update([
                'starts_at' => DB::raw('start_date'),
            ]);

        // Populate ends_at from end_date where ends_at is NULL
        DB::table('academic_subscriptions')
            ->whereNotNull('end_date')
            ->whereNull('ends_at')
            ->update([
                'ends_at' => DB::raw('end_date'),
            ]);

        // Log the migration results
        $updated = DB::table('academic_subscriptions')
            ->whereNotNull('starts_at')
            ->count();

        if ($updated > 0) {
            \Log::info("Populated standardized date fields for {$updated} academic subscriptions");
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // Restore NULL values to starts_at and ends_at
        // (Only if they match start_date/end_date to avoid data loss)
        DB::table('academic_subscriptions')
            ->whereColumn('starts_at', 'start_date')
            ->update(['starts_at' => null]);

        DB::table('academic_subscriptions')
            ->whereColumn('ends_at', 'end_date')
            ->update(['ends_at' => null]);
    }
};
