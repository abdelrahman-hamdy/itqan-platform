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
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 50);
            $table->string('event_type', 50);
            $table->string('event_id')->unique()->comment('Unique event ID for idempotency');
            $table->string('transaction_id')->nullable();
            $table->string('status', 50);
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->string('currency', 10)->default('SAR');
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'transaction_id']);
            $table->index(['payment_id', 'status']);
            $table->index('is_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
