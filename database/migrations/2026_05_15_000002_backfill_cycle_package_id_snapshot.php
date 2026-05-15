<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #4 — Backfill the per-cycle `package_id` snapshot from the parent
 * subscription row.
 *
 * The 2026_05_14_000002_add_pricing_trust_to_subscription_cycles migration
 * defaulted `pricing_source='package'` for every existing row but did NOT
 * populate `cycle.package_id` from the parent subscription. As a result the
 * v2 invariant checker raises INV-D4 ("pricing_source=package but
 * package_id snapshot is NULL") on ~741 legacy cycles.
 *
 * This migration is a one-time, idempotent, reversible-by-NULL backfill —
 * it only fills NULLs and only when the parent subscription has a
 * non-NULL `package_id`. Cycles intentionally created with a sale_price /
 * manual_override source are not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_cycles')) {
            return;
        }

        $maps = [
            'quran_subscription' => 'quran_subscriptions',
            'academic_subscription' => 'academic_subscriptions',
            'course_subscription' => 'course_subscriptions',
        ];

        foreach ($maps as $morph => $parentTable) {
            if (! Schema::hasTable($parentTable) || ! Schema::hasColumn($parentTable, 'package_id')) {
                continue;
            }

            DB::statement(<<<SQL
                UPDATE subscription_cycles c
                INNER JOIN {$parentTable} s ON s.id = c.subscribable_id
                SET c.package_id = s.package_id
                WHERE c.subscribable_type = ?
                  AND c.package_id IS NULL
                  AND c.pricing_source = 'package'
                  AND s.package_id IS NOT NULL
            SQL, [$morph]);
        }
    }

    public function down(): void
    {
        // No-op: we cannot tell which cycles had a NULL package_id before the
        // backfill vs. those that always had one. Down would need a
        // pre-backfill snapshot to be reversible safely.
    }
};
