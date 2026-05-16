<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `deleted_at` to subscription_cycles so SubscriptionCycle can adopt the
 * SoftDeletes trait. Initial use case is hiding the 34 orphan cycles
 * (parent subscription was deleted, cycle row dangled) so the INV-D2
 * audit's ORPHAN_SUBSCRIPTION bucket goes to zero without losing the
 * historical row. Future soft-deletes (e.g., admin cycle cancellation,
 * test cleanup) become reversible without writing a `is_deleted` flag
 * column or relying on `cycle_state='archived'`.
 *
 * The column is nullable + indexed so the global SoftDeletes scope can
 * filter efficiently. Indexed-only, no FK constraints — pure metadata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_cycles', function (Blueprint $table) {
            $table->softDeletes()->index();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_cycles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
