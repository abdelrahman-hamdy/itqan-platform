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
            $table->dropColumn(['subject_ids', 'grade_level_ids']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_packages', function (Blueprint $table) {
            $table->json('subject_ids')->nullable()->after('package_type');
            $table->json('grade_level_ids')->nullable()->after('subject_ids');
        });
    }
};