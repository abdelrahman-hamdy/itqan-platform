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
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            // Drop columns that are no longer needed (if they exist)
            $columnsToDrop = [];
            foreach (['department', 'supervision_level', 'monitoring_permissions', 'reports_access_level', 'contract_end_date'] as $column) {
                if (Schema::hasColumn('supervisor_profiles', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->enum('department', ['quran', 'academic', 'recorded_courses', 'general'])->default('general')->after('supervisor_code');
            $table->enum('supervision_level', ['junior', 'senior', 'lead'])->default('junior')->after('department');
            $table->json('monitoring_permissions')->nullable()->after('assigned_teachers');
            $table->enum('reports_access_level', ['basic', 'detailed', 'full'])->default('basic')->after('monitoring_permissions');
            $table->date('contract_end_date')->nullable()->after('hired_date');
        });
    }
};
