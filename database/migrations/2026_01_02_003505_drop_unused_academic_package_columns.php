<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused/orphaned columns from academic_packages table.
 *
 * - name_en/description_en: No bilingual support implemented
 * - created_by/updated_by: Never read in application
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints if they exist
        Schema::table('academic_packages', function (Blueprint $table) {
            $foreignKeys = [
                'academic_packages_created_by_foreign',
                'academic_packages_updated_by_foreign',
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
        Schema::table('academic_packages', function (Blueprint $table) {
            $columnsToDrop = [
                'name_en',        // No bilingual support implemented
                'description_en', // No bilingual support implemented
                'created_by',     // Never read in application
                'updated_by',     // Never read in application
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('academic_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_packages', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
            if (!Schema::hasColumn('academic_packages', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_ar');
            }
            if (!Schema::hasColumn('academic_packages', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('academic_packages', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
