<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused/orphaned columns from quran_individual_circles table.
 *
 * - preferred_times: In model but never displayed in UI
 * - created_by/updated_by: Never read in application
 *
 * NOTE: sessions_scheduled, sessions_completed, sessions_remaining are kept
 * because they're displayed in the table view for tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints if they exist
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $foreignKeys = [
                'quran_individual_circles_created_by_foreign',
                'quran_individual_circles_updated_by_foreign',
            ];

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }
        });

        // Now drop the columns
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $columnsToDrop = [
                'preferred_times', // In model but no UI field
                'created_by',      // Never read in application
                'updated_by',      // Never read in application
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('quran_individual_circles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            if (! Schema::hasColumn('quran_individual_circles', 'preferred_times')) {
                $table->json('preferred_times')->nullable();
            }
            if (! Schema::hasColumn('quran_individual_circles', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (! Schema::hasColumn('quran_individual_circles', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
