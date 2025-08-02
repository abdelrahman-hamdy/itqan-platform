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
        Schema::table('users', function (Blueprint $table) {
            // Add indexes for performance (only if they don't exist)
            if (!Schema::hasIndex('users', 'users_email_academy_id_index')) {
                $table->index(['email', 'academy_id']);
            }
            
            if (!Schema::hasIndex('users', 'users_user_type_academy_id_index')) {
                $table->index(['user_type', 'academy_id']);
            }
            
            if (!Schema::hasIndex('users', 'users_status_is_active_index')) {
                $table->index(['status', 'is_active']);
            }
            
            if (!Schema::hasIndex('users', 'users_email_verification_token_index')) {
                $table->index('email_verification_token');
            }
            
            if (!Schema::hasIndex('users', 'users_phone_verification_token_index')) {
                $table->index('phone_verification_token');
            }
            
            if (!Schema::hasIndex('users', 'users_password_reset_token_index')) {
                $table->index('password_reset_token');
            }
            
            if (!Schema::hasIndex('users', 'users_parent_id_index')) {
                $table->index('parent_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes if they exist (excluding parent_id which is needed for foreign key)
            if (Schema::hasIndex('users', 'users_email_academy_id_index')) {
                $table->dropIndex(['email', 'academy_id']);
            }
            if (Schema::hasIndex('users', 'users_user_type_academy_id_index')) {
                $table->dropIndex(['user_type', 'academy_id']);
            }
            if (Schema::hasIndex('users', 'users_status_is_active_index')) {
                $table->dropIndex(['status', 'is_active']);
            }
            if (Schema::hasIndex('users', 'users_email_verification_token_index')) {
                $table->dropIndex(['email_verification_token']);
            }
            if (Schema::hasIndex('users', 'users_phone_verification_token_index')) {
                $table->dropIndex(['phone_verification_token']);
            }
            if (Schema::hasIndex('users', 'users_password_reset_token_index')) {
                $table->dropIndex(['password_reset_token']);
            }
            // Note: parent_id index is not dropped as it's required for the foreign key constraint
        });
    }
};
