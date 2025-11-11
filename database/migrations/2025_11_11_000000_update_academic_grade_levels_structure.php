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
        Schema::table('academic_grade_levels', function (Blueprint $table) {
            // Remove color_code column completely
            $table->dropColumn('color_code');
            
            // Add description_en column
            $table->text('description_en')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_grade_levels', function (Blueprint $table) {
            // Re-add color_code column
            $table->string('color_code')->default('#10B981')->after('description_en');
            
            // Remove description_en column
            $table->dropColumn('description_en');
        });
    }
};
