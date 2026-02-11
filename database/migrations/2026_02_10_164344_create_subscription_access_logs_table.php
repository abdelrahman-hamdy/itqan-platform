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
        Schema::create('subscription_access_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->morphs('subscription'); // subscription_type, subscription_id
            $table->uuid('user_id');
            $table->string('platform', 20); // web|mobile
            $table->string('action', 50); // access_granted|access_denied|purchase_attempted
            $table->string('resource_type', 100)->nullable(); // session|course|lesson
            $table->uuid('resource_id')->nullable();
            $table->json('metadata')->nullable(); // IP, user_agent, error_reason
            $table->timestamps();

            $table->index(['subscription_type', 'subscription_id', 'created_at'], 'sal_sub_type_id_created_idx');
            $table->index(['user_id', 'platform', 'created_at'], 'sal_user_platform_created_idx');
            $table->index(['action', 'created_at'], 'sal_action_created_idx');
            $table->index(['tenant_id']);
            // Note: No foreign key constraints - tenant_id is managed via global scopes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_access_logs');
    }
};
