<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to track tokenized payments and recurring billing
     * on the payments table.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Reference to saved payment method used (if any)
            $table->foreignId('saved_payment_method_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('saved_payment_methods')
                ->nullOnDelete();

            // The token used for this specific payment (for audit trail)
            $table->string('card_token', 500)->nullable()->after('saved_payment_method_id');

            // Whether user opted to save this card for future use
            $table->boolean('save_card')->default(false)->after('card_token');

            // Whether this is a recurring/auto-renewal payment
            $table->boolean('is_recurring')->default(false)->after('save_card');

            // Type of recurring payment
            $table->string('recurring_type', 30)->nullable()->after('is_recurring'); // 'subscription_renewal', 'installment', etc.

            // Index for finding payments by saved method
            $table->index('saved_payment_method_id', 'payments_saved_method_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['saved_payment_method_id']);
            $table->dropIndex('payments_saved_method_idx');
            $table->dropColumn([
                'saved_payment_method_id',
                'card_token',
                'save_card',
                'is_recurring',
                'recurring_type',
            ]);
        });
    }
};
