<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `current_cycle_id` and `cycle_count` to the session-based subscription tables
 * so a subscription row always knows which `subscription_cycles` row is its active cycle.
 *
 * `current_cycle_id` = the active cycle row in `subscription_cycles`
 * `cycle_count`      = total number of cycles this subscription thread has gone through
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('current_cycle_id')->nullable()->after('previous_subscription_id');
            $table->unsignedInteger('cycle_count')->default(1)->after('current_cycle_id');

            $table->foreign('current_cycle_id', 'fk_quran_sub_current_cycle')
                ->references('id')->on('subscription_cycles')
                ->nullOnDelete();
            $table->index('current_cycle_id', 'idx_quran_sub_current_cycle');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('current_cycle_id')->nullable()->after('previous_subscription_id');
            $table->unsignedInteger('cycle_count')->default(1)->after('current_cycle_id');

            $table->foreign('current_cycle_id', 'fk_academic_sub_current_cycle')
                ->references('id')->on('subscription_cycles')
                ->nullOnDelete();
            $table->index('current_cycle_id', 'idx_academic_sub_current_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_quran_sub_current_cycle');
            $table->dropIndex('idx_quran_sub_current_cycle');
            $table->dropColumn(['current_cycle_id', 'cycle_count']);
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_academic_sub_current_cycle');
            $table->dropIndex('idx_academic_sub_current_cycle');
            $table->dropColumn(['current_cycle_id', 'cycle_count']);
        });
    }
};
