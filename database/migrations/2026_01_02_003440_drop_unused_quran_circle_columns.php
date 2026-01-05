<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused/orphaned columns from quran_circles table.
 *
 * These columns were identified as:
 * - Never read in application logic
 * - Not displayed in any UI
 * - Legacy fields superseded by better implementations
 * - Bilingual fields with no i18n support implemented
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints if they exist
        Schema::table('quran_circles', function (Blueprint $table) {
            // Drop foreign keys for created_by and updated_by
            $foreignKeys = [
                'quran_circles_created_by_foreign',
                'quran_circles_updated_by_foreign',
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
        Schema::table('quran_circles', function (Blueprint $table) {
            $columnsToDrop = [
                'circle_type',        // Superseded by 'specialization' field
                'avg_rating',         // Never calculated or used
                'total_reviews',      // Never incremented or displayed
                'completion_rate',    // Never calculated
                'dropout_rate',       // Never calculated
                'name_en',            // No bilingual support implemented
                'description_en',     // No bilingual support implemented
                'created_by',         // Never read in application
                'updated_by',         // Never read in application
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('quran_circles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            if (!Schema::hasColumn('quran_circles', 'circle_type')) {
                $table->string('circle_type')->nullable()->after('specialization');
            }
            if (!Schema::hasColumn('quran_circles', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->nullable();
            }
            if (!Schema::hasColumn('quran_circles', 'total_reviews')) {
                $table->unsignedInteger('total_reviews')->default(0);
            }
            if (!Schema::hasColumn('quran_circles', 'completion_rate')) {
                $table->decimal('completion_rate', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('quran_circles', 'dropout_rate')) {
                $table->decimal('dropout_rate', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('quran_circles', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
            if (!Schema::hasColumn('quran_circles', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_ar');
            }
            if (!Schema::hasColumn('quran_circles', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('quran_circles', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
