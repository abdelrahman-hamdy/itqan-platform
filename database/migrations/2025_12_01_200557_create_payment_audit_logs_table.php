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
        Schema::create('payment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('gateway', 50)->nullable();
            $table->string('status_from', 50)->nullable();
            $table->string('status_to', 50)->nullable();
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->string('currency', 10)->default('SAR');
            $table->string('transaction_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'action']);
            $table->index(['academy_id', 'created_at']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_audit_logs');
    }
};
