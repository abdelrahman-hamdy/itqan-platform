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
     * Add session tracking columns to academic_subscriptions to align with QuranSubscription pattern.
     * These columns track the lifecycle of sessions within a subscription:
     * - total_sessions: Total sessions available in this subscription
     * - total_sessions_scheduled: Sessions that have been scheduled
     * - total_sessions_completed: Sessions completed successfully
     * - total_sessions_missed: Sessions missed or cancelled
     * - sessions_used: Counter for used sessions (backwards compatibility)
     * - sessions_remaining: Sessions left to use
     */
    public function up(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Total sessions for the subscription period
            $table->unsignedInteger('total_sessions')->default(8)->after('sessions_per_month');

            // Session tracking counters
            $table->unsignedInteger('total_sessions_scheduled')->default(0)->after('total_sessions');
            $table->unsignedInteger('total_sessions_completed')->default(0)->after('total_sessions_scheduled');
            $table->unsignedInteger('total_sessions_missed')->default(0)->after('total_sessions_completed');

            // For backwards compatibility with Quran pattern
            $table->unsignedInteger('sessions_used')->default(0)->after('total_sessions_missed');
            $table->unsignedInteger('sessions_remaining')->nullable()->after('sessions_used');
        });

        // Initialize sessions_remaining based on sessions_per_month for existing records
        // This provides a sensible default for existing subscriptions
        DB::table('academic_subscriptions')->update([
            'sessions_remaining' => DB::raw('COALESCE(sessions_per_month, total_sessions)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'total_sessions',
                'total_sessions_scheduled',
                'total_sessions_completed',
                'total_sessions_missed',
                'sessions_used',
                'sessions_remaining',
            ]);
        });
    }
};
