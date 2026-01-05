<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidate bilingual fields in quran_packages table.
 *
 * - Drop English fields (name_en, description_en)
 * - Rename Arabic fields to simple names (name_ar → name, description_ar → description)
 * - Drop unused audit fields (created_by, updated_by)
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints for audit fields if they exist
        Schema::table('quran_packages', function (Blueprint $table) {
            $foreignKeys = [
                'quran_packages_created_by_foreign',
                'quran_packages_updated_by_foreign',
            ];

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }
        });

        // Drop English columns and rename Arabic columns
        Schema::table('quran_packages', function (Blueprint $table) {
            // Drop English columns
            if (Schema::hasColumn('quran_packages', 'name_en')) {
                $table->dropColumn('name_en');
            }
            if (Schema::hasColumn('quran_packages', 'description_en')) {
                $table->dropColumn('description_en');
            }

            // Drop unused audit columns
            if (Schema::hasColumn('quran_packages', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('quran_packages', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });

        // Rename Arabic columns to simple names
        Schema::table('quran_packages', function (Blueprint $table) {
            if (Schema::hasColumn('quran_packages', 'name_ar')) {
                $table->renameColumn('name_ar', 'name');
            }
            if (Schema::hasColumn('quran_packages', 'description_ar')) {
                $table->renameColumn('description_ar', 'description');
            }
        });
    }

    public function down(): void
    {
        // Rename back to Arabic suffixed columns
        Schema::table('quran_packages', function (Blueprint $table) {
            if (Schema::hasColumn('quran_packages', 'name')) {
                $table->renameColumn('name', 'name_ar');
            }
            if (Schema::hasColumn('quran_packages', 'description')) {
                $table->renameColumn('description', 'description_ar');
            }
        });

        // Re-add English and audit columns
        Schema::table('quran_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('quran_packages', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
            if (!Schema::hasColumn('quran_packages', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_ar');
            }
            if (!Schema::hasColumn('quran_packages', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('quran_packages', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
