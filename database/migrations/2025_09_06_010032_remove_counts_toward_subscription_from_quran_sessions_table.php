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
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Check if the column exists before trying to remove it
            if (Schema::hasColumn('quran_sessions', 'counts_toward_subscription')) {
                // Try to drop indexes that use the column first (if they exist)
                try {
                    $table->dropIndex('idx_ind_circle_counts');
                } catch (\Exception $e) {
                    // Index might not exist, continue anyway
                }

                // Remove the counts_toward_subscription column
                $table->dropColumn('counts_toward_subscription');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            //
        });
    }
};
