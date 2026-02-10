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
        Schema::table('academic_packages', function (Blueprint $table) {
            $table->dropIndex('academic_packages_package_type_is_active_index');
            $table->dropColumn('package_type');
        });
    }

    public function down(): void
    {
        Schema::table('academic_packages', function (Blueprint $table) {
            $table->enum('package_type', ['individual', 'group'])->default('individual')->after('description');
            $table->index(['package_type', 'is_active'], 'academic_packages_package_type_is_active_index');
        });
    }
};
