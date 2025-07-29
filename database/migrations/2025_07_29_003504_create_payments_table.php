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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            
            // Payment Identification
            $table->string('payment_code')->unique();
            $table->enum('payment_method', [
                'credit_card', 'debit_card', 'bank_transfer', 'wallet', 'cash',
                'mada', 'visa', 'mastercard', 'apple_pay', 'stc_pay', 'urpay'
            ]);
            $table->enum('payment_gateway', [
                'tap', 'moyasar', 'payfort', 'hyperpay', 'paytabs', 'manual'
            ])->nullable();
            
            // Gateway Information
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->enum('payment_type', ['subscription', 'course', 'session', 'other'])->default('subscription');
            
            // Amount Information
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 8, 4)->default(1);
            $table->decimal('amount_in_base_currency', 10, 2)->nullable();
            
            // Fees and Taxes
            $table->decimal('fees', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(15);
            
            // Discounts
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_code')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 
                'cancelled', 'refunded', 'partially_refunded'
            ])->default('pending');
            $table->enum('payment_status', [
                'pending', 'processing', 'paid', 'failed', 'cancelled'
            ])->default('pending');
            $table->string('gateway_status')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Gateway Response
            $table->json('gateway_response')->nullable();
            
            // Timestamps
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            // Refund Information
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->string('refund_reference')->nullable();
            
            // Receipt Information
            $table->string('receipt_url')->nullable();
            $table->string('receipt_number')->nullable();
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['subscription_id']);
            $table->index(['payment_method', 'status']);
            $table->index(['payment_gateway', 'status']);
            $table->index(['payment_date', 'status']);
            $table->index(['gateway_transaction_id']);
            $table->index(['payment_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
