<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links `payments` to a specific `subscription_cycles` row so we can tell which
 * billing cycle a payment settled. Needed for per-cycle payment history.
 *
 * Payment.payable_id still points at the subscription thread (polymorphic);
 * this column pins the payment to a specific cycle within that thread.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_cycle_id')->nullable()->after('payable_id');

            $table->foreign('subscription_cycle_id', 'fk_payments_subscription_cycle')
                ->references('id')->on('subscription_cycles')
                ->nullOnDelete();
            $table->index('subscription_cycle_id', 'idx_payments_subscription_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('fk_payments_subscription_cycle');
            $table->dropIndex('idx_payments_subscription_cycle');
            $table->dropColumn('subscription_cycle_id');
        });
    }
};
