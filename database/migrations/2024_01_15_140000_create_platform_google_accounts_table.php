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
        Schema::create('platform_google_accounts', function (Blueprint $table) {
            $table->id();
            
            // Academy relationship
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            
            // Account details
            $table->string('account_name'); // e.g., "Academy Fallback Account"
            $table->string('google_email');
            $table->string('google_id');
            $table->string('account_type')->default('fallback'); // fallback, primary, backup
            
            // Token data (encrypted)
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('scope')->nullable();
            
            // Usage tracking
            $table->integer('sessions_created')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Rate limiting
            $table->integer('daily_usage')->default(0);
            $table->date('usage_reset_date')->default(now()->toDateString());
            $table->integer('daily_limit')->default(100); // meetings per day
            
            // Error tracking
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->integer('consecutive_errors')->default(0);
            
            // Configuration
            $table->json('meeting_defaults')->nullable(); // default meeting settings
            $table->boolean('auto_record')->default(false);
            $table->integer('default_duration')->default(60); // minutes
            
            $table->timestamps();
            
            // Indexes
            $table->index(['academy_id', 'account_type', 'is_active']);
            $table->index(['google_email']);
            $table->index(['usage_reset_date', 'daily_usage']);
            
            // Ensure unique Google account per academy
            $table->unique(['academy_id', 'google_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_google_accounts');
    }
};