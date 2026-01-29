<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the saved_payment_methods table for storing tokenized
     * payment methods (cards, wallets) for recurring billing.
     */
    public function up(): void
    {
        Schema::create('saved_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Gateway identification
            $table->string('gateway', 50)->index(); // 'paymob', 'easykash', etc.

            // Token data (encrypted at application level for sensitive data)
            $table->string('token', 500)->index(); // Paymob card_token or similar
            $table->string('gateway_customer_id')->nullable(); // External customer ID if applicable

            // Payment method type
            $table->string('type', 30)->default('card'); // card, wallet, apple_pay, bank_account

            // Card-specific details (for display purposes only)
            $table->string('brand', 30)->nullable(); // visa, mastercard, meeza, amex
            $table->string('last_four', 4)->nullable();
            $table->string('expiry_month', 2)->nullable();
            $table->string('expiry_year', 4)->nullable();
            $table->string('holder_name')->nullable();

            // User-facing
            $table->string('display_name')->nullable(); // User-defined nickname for the card
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // Metadata and tracking
            $table->json('metadata')->nullable(); // Additional gateway-specific data
            $table->json('billing_address')->nullable(); // Stored billing address
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('verified_at')->nullable(); // When card was verified (e.g., 3DS)
            $table->timestamp('expires_at')->nullable(); // Token expiration if applicable

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->unique(['user_id', 'gateway', 'token'], 'unique_user_gateway_token');
            $table->index(['user_id', 'is_default', 'is_active'], 'user_default_active');
            $table->index(['academy_id', 'gateway'], 'academy_gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_payment_methods');
    }
};
