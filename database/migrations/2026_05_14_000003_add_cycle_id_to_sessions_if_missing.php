<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A.6 — INV-E1/E2 prerequisite.
 *
 * Sessions must know their anchor cycle so RebuildFutureSessionsForCycle can
 * find the rows it owns when a renewal changes the package mid-thread.
 *
 * The actual column was added by
 *   2026_05_04_120000_add_subscription_cycle_id_to_sessions_and_attendances.php
 * which named it `subscription_cycle_id` (not `cycle_id`) and added it to
 * `quran_sessions` + `academic_sessions` only. `interactive_course_sessions`
 * is intentionally excluded — CourseSubscription is enrollment-based and does
 * not count sessions.
 *
 * This migration is therefore a defensive no-op on existing environments — it
 * only fires if the column is somehow missing (e.g. a fresh DB built from an
 * older schema dump, or a partial migration state). On every env that ran the
 * 2026-05-04 migration, all `Schema::hasColumn` checks return true and the
 * migration adds nothing.
 *
 * Run order: comes AFTER 2026_05_14_000002, so the pricing trust columns and
 * this guard land in a single Phase A.6 batch.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quran_sessions', 'subscription_cycle_id')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('subscription_cycle_id')->nullable()->after('quran_subscription_id');

                $table->foreign('subscription_cycle_id', 'fk_quran_sessions_subscription_cycle')
                    ->references('id')->on('subscription_cycles')
                    ->nullOnDelete();
                $table->index(['subscription_cycle_id', 'status'], 'idx_quran_sessions_cycle_status');
            });
        }

        if (! Schema::hasColumn('academic_sessions', 'subscription_cycle_id')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('subscription_cycle_id')->nullable()->after('academic_subscription_id');

                $table->foreign('subscription_cycle_id', 'fk_academic_sessions_subscription_cycle')
                    ->references('id')->on('subscription_cycles')
                    ->nullOnDelete();
                $table->index(['subscription_cycle_id', 'status'], 'idx_academic_sessions_cycle_status');
            });
        }
    }

    public function down(): void
    {
        // Intentional no-op. The original 2026_05_04 migration owns these
        // columns; if this migration added them at all, rolling them back
        // here would break the 2026_05_04 migration's `down()` and produce
        // inconsistent migration state across environments.
    }
};
