<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANT: Before running this migration, ensure data integrity is fixed by running:
     * php artisan academy:fix-admin-integrity --fix
     *
     * This migration adds a unique constraint to ensure each admin can only be assigned
     * to one academy at a time, enforcing the one-to-one relationship.
     */
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            // Add unique constraint on admin_id
            // This allows NULL values (academies without admin) but prevents
            // the same admin being assigned to multiple academies
            $table->unique('admin_id', 'academies_admin_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropUnique('academies_admin_id_unique');
        });
    }
};
