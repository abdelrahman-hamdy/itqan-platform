<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Payment Intent/Session tracking (add after gateway_transaction_id)
            $table->string('gateway_intent_id')->nullable()->after('gateway_transaction_id')
                ->comment('Gateway payment intent/session ID');
            $table->string('gateway_order_id')->nullable()->after('gateway_intent_id')
                ->comment('Gateway order ID');
            $table->string('client_secret')->nullable()->after('gateway_order_id')
                ->comment('Client secret for frontend');

            // Payment method details (add after payment_gateway)
            $table->string('payment_method_type', 50)->nullable()->after('payment_gateway')
                ->comment('e.g., card, wallet, bank_transfer');
            $table->string('card_brand', 50)->nullable()->after('payment_method_type');
            $table->string('card_last_four', 4)->nullable()->after('card_brand');

            // URLs for redirect flow
            $table->string('redirect_url')->nullable()->after('client_secret');
            $table->string('iframe_url')->nullable()->after('redirect_url');
            $table->string('callback_url')->nullable()->after('iframe_url');

            // Intent expiry
            $table->timestamp('intent_expires_at')->nullable()->after('callback_url');

            // Paid timestamp (if not already present)
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_date');
            }

            // Indexes
            $table->index('gateway_intent_id');
            $table->index('gateway_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_intent_id']);
            $table->dropIndex(['gateway_order_id']);

            $table->dropColumn([
                'gateway_intent_id',
                'gateway_order_id',
                'client_secret',
                'payment_method_type',
                'card_brand',
                'card_last_four',
                'redirect_url',
                'iframe_url',
                'callback_url',
                'intent_expires_at',
            ]);

            if (Schema::hasColumn('payments', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });
    }
};
