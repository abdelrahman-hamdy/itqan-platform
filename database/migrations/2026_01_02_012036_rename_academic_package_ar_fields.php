<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename Arabic fields to simple names in academic_packages table.
 * Note: English columns (name_en, description_en) were already dropped in a previous migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_packages', function (Blueprint $table) {
            if (Schema::hasColumn('academic_packages', 'name_ar')) {
                $table->renameColumn('name_ar', 'name');
            }
            if (Schema::hasColumn('academic_packages', 'description_ar')) {
                $table->renameColumn('description_ar', 'description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_packages', function (Blueprint $table) {
            if (Schema::hasColumn('academic_packages', 'name')) {
                $table->renameColumn('name', 'name_ar');
            }
            if (Schema::hasColumn('academic_packages', 'description')) {
                $table->renameColumn('description', 'description_ar');
            }
        });
    }
};
