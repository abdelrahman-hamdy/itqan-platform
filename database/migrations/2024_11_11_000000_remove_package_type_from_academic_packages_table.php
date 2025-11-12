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
        // Only attempt to drop column if table exists and column exists
        if (Schema::hasTable('academic_packages') && Schema::hasColumn('academic_packages', 'package_type')) {
            Schema::table('academic_packages', function (Blueprint $table) {
                $table->dropColumn('package_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only attempt to add column back if table exists and column doesn't exist
        if (Schema::hasTable('academic_packages') && !Schema::hasColumn('academic_packages', 'package_type')) {
            Schema::table('academic_packages', function (Blueprint $table) {
                $table->enum('package_type', ['individual', 'group'])->default('individual')->after('description_en');
            });
        }
    }
};
