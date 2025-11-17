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
        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns
            $table->string('notification_type')->nullable()->after('type');
            $table->string('category')->nullable()->after('notification_type');
            $table->string('icon')->nullable()->after('category');
            $table->string('icon_color')->nullable()->after('icon');
            $table->string('action_url')->nullable()->after('data');
            $table->json('metadata')->nullable()->after('action_url');
            $table->boolean('is_important')->default(false)->after('metadata');
            $table->string('tenant_id')->nullable()->after('notifiable_id');

            // Add indexes for better performance
            $table->index('notification_type');
            $table->index('category');
            $table->index('tenant_id');
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['notification_type']);
            $table->dropIndex(['category']);
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->dropIndex(['created_at']);

            // Drop columns
            $table->dropColumn([
                'notification_type',
                'category',
                'icon',
                'icon_color',
                'action_url',
                'metadata',
                'is_important',
                'tenant_id'
            ]);
        });
    }
};