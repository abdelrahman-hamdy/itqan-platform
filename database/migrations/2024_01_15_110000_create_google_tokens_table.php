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
        Schema::create('google_tokens', function (Blueprint $table) {
            $table->id();
            
            // User and academy relationship
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            
            // Token data (encrypted)
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('token_type')->default('Bearer');
            $table->json('scope')->nullable(); // granted permissions
            
            // Token metadata
            $table->string('token_status')->default('active'); // active, expired, revoked
            $table->integer('refresh_count')->default(0);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            
            // Error tracking
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->integer('consecutive_errors')->default(0);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'academy_id']);
            $table->index(['token_status', 'expires_at']);
            $table->index(['last_used_at']);
            
            // Ensure one active token per user
            $table->unique(['user_id', 'token_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_tokens');
    }
};