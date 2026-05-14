<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A.6 — Pricing trust model (R4).
 *
 * Adds the three columns required by INV-D1..INV-D4
 * (see docs/subscription-invariants.md §2 Group D + §7):
 *
 *   - pricing_source            VARCHAR(32) NOT NULL DEFAULT 'package'
 *                               enum-of-strings: 'package' | 'sale_price' | 'manual_override'
 *   - pricing_override_reason   VARCHAR(255) NULL
 *   - pricing_override_actor_id BIGINT UNSIGNED NULL FK -> users.id ON DELETE SET NULL
 *
 * Existing rows are stamped with `pricing_source = 'package'` on apply. This
 * is the DEFAULT for new rows so the seed is idempotent. It is also the
 * "innocent until proven guilty" assumption — the separate audit command
 *   php artisan subscriptions:audit-pricing-trust
 * scans every cycle and flags rows where `final_price` disagrees with
 * `PricingResolver::resolvePriceFromPackage($package, $billingCycle) - discount_amount`,
 * surfacing every Sharouq-shaped row that should actually be tagged
 * `sale_price` or `manual_override`. Operators reclassify those manually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_cycles', function (Blueprint $table) {
            $table->string('pricing_source', 32)
                ->default('package')
                ->after('final_price');

            $table->string('pricing_override_reason', 255)
                ->nullable()
                ->after('pricing_source');

            $table->unsignedBigInteger('pricing_override_actor_id')
                ->nullable()
                ->after('pricing_override_reason');

            $table->foreign('pricing_override_actor_id', 'sub_cycles_pricing_actor_fk')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index('pricing_source', 'sub_cycles_pricing_source_idx');
        });

        // Stamp every pre-existing cycle with the default trust source.
        // Idempotent: the column already defaults to 'package' for new rows
        // and DEFAULT is applied to existing rows when the column is added,
        // but we re-state it explicitly so the seed is deterministic regardless
        // of how the DB engine interprets DEFAULT-on-add.
        DB::statement("UPDATE subscription_cycles SET pricing_source = 'package' WHERE pricing_source IS NULL OR pricing_source = ''");
    }

    public function down(): void
    {
        Schema::table('subscription_cycles', function (Blueprint $table) {
            $table->dropForeign('sub_cycles_pricing_actor_fk');
            $table->dropIndex('sub_cycles_pricing_source_idx');
            $table->dropColumn(['pricing_source', 'pricing_override_reason', 'pricing_override_actor_id']);
        });
    }
};
