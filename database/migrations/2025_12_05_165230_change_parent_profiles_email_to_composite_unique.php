<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes email unique constraint from global to composite (email + academy_id).
     * This enables true multi-tenancy where the same email can be used in different academies.
     */
    public function up(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            // Drop the global unique constraint on email
            $table->dropUnique('parent_profiles_email_unique');

            // Add composite unique constraint on email + academy_id
            // This allows same email in different academies (true multi-tenancy)
            $table->unique(['email', 'academy_id'], 'parent_profiles_email_academy_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * IMPORTANT: This will fail if duplicate emails exist across academies.
     */
    public function down(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            // Revert: Drop composite constraint
            $table->dropUnique('parent_profiles_email_academy_unique');

            // Re-add global email unique constraint
            // NOTE: This will fail if same email exists in multiple academies
            $table->unique('email', 'parent_profiles_email_unique');
        });
    }
};
