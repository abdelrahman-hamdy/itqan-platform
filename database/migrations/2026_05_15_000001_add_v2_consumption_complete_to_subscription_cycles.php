<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E1 — gate the SubscriptionReconciler against pre-v2-flip cycles.
 *
 * Pre-v2 attendance writes only ever updated `cycle.sessions_used` (legacy
 * path). The `session_consumption` table was empty for any cycle whose
 * sessions ran before the flip, so the reconciler's INV-B3 recount would
 * have zeroed `sessions_used` on every legacy cycle the moment a v2 mutator
 * touched it.
 *
 * This column is the per-cycle "v2 truth-source authority" flag:
 *   - false (default) → reconciler MUST NOT recount sessions_used from
 *     session_consumption; the legacy aggregate is authoritative.
 *   - true            → consumption table is the source of truth (set at
 *     cycle-create-time post-flip, or after a confirmed legacy backfill).
 *
 * The reconciler also pairs this with a config-driven flip_cutoff timestamp
 * so brand-new cycles created post-flip are never gated even if the column
 * defaults to false — the date check covers the bootstrap window.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_cycles', function (Blueprint $table) {
            $table->boolean('v2_consumption_complete')
                ->default(false)
                ->after('pricing_override_actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_cycles', function (Blueprint $table) {
            $table->dropColumn('v2_consumption_complete');
        });
    }
};
