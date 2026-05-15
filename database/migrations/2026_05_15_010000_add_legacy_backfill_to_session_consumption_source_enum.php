<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `legacy_backfill` to the `session_consumption.source` enum.
 *
 * Used by `subscriptions:fix-pattern-a-legacy-backfill` to synthesize
 * consumption rows from the legacy `subscription_counted=true` flag. These
 * rows must be the weakest signal in the precedence cascade so any future
 * canonical write (auto_attendance / teacher_report / admin_manual) can
 * override them.
 *
 * Pure DDL — no row-level changes. Reversible by enum value removal but
 * MySQL refuses to remove an enum value while rows still reference it, so
 * the `down` migration is best-effort (skips if any backfill rows exist).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('session_consumption')) {
            return;
        }

        DB::statement("ALTER TABLE session_consumption
            MODIFY COLUMN source ENUM(
                'admin_manual',
                'teacher_report',
                'auto_attendance',
                'legacy_backfill'
            ) NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('session_consumption')) {
            return;
        }

        $hasBackfill = DB::table('session_consumption')
            ->where('source', 'legacy_backfill')
            ->exists();

        if ($hasBackfill) {
            // Refuse to drop the value while rows reference it. The cleanup
            // rollback procedure (docs/cleanup/rollback-2026-05-15.md) handles
            // row-level reversal first.
            return;
        }

        DB::statement("ALTER TABLE session_consumption
            MODIFY COLUMN source ENUM(
                'admin_manual',
                'teacher_report',
                'auto_attendance'
            ) NOT NULL");
    }
};
