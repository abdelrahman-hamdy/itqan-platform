<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Auto-verify all existing users' emails.
     * This is a one-time migration for the email verification feature rollout.
     * All existing users are trusted since they've been using the system already.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     * Note: This cannot be truly reversed as we cannot know which users
     * were previously unverified. Setting to null would break existing users.
     */
    public function down(): void
    {
        // Cannot reverse - would need to track original state
        // Intentionally left empty to avoid data loss
    }
};
