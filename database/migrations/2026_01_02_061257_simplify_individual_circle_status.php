<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Simplify status from enum (pending, active, completed, suspended, cancelled)
     * to boolean (is_active) like group circles.
     *
     * The subscription status should control access, not the circle status.
     */
    public function up(): void
    {
        // First, add the new boolean column
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
        });

        // Migrate data: active = true, everything else = false
        DB::statement("UPDATE quran_individual_circles SET is_active = CASE WHEN status IN ('active', 'pending') THEN 1 ELSE 0 END");

        // Drop the old enum column
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'completed', 'suspended', 'cancelled'])
                ->default('pending')
                ->after('is_active');
        });

        // Migrate data back
        DB::statement("UPDATE quran_individual_circles SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'suspended' END");

        // Drop the boolean column
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
