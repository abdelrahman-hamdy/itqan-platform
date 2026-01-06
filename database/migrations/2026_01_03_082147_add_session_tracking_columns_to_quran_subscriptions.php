<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Standardize session tracking columns to match academic_subscriptions pattern:
     * - total_sessions_scheduled: Sessions that have been scheduled
     * - total_sessions_completed: Sessions that were completed successfully
     * - total_sessions_missed: Sessions that were missed/cancelled
     *
     * The existing sessions_used and sessions_remaining columns will be kept
     * as computed values for backwards compatibility.
     */
    public function up(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            // Add new session tracking columns (matching academic_subscriptions pattern)
            $table->unsignedInteger('total_sessions_scheduled')->default(0)->after('total_sessions');
            $table->unsignedInteger('total_sessions_completed')->default(0)->after('total_sessions_scheduled');
            $table->unsignedInteger('total_sessions_missed')->default(0)->after('total_sessions_completed');
        });

        // Migrate existing data: sessions_used represents completed sessions
        DB::table('quran_subscriptions')->update([
            'total_sessions_completed' => DB::raw('sessions_used'),
            // For scheduled, we assume all completed sessions were scheduled
            // plus any remaining sessions that might be scheduled
            'total_sessions_scheduled' => DB::raw('sessions_used'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'total_sessions_scheduled',
                'total_sessions_completed',
                'total_sessions_missed',
            ]);
        });
    }
};
