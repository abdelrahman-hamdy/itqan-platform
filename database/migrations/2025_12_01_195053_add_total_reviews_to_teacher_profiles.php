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
        // Add total_reviews to quran_teacher_profiles
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->unsignedInteger('total_reviews')->default(0)->after('rating');
        });

        // Add total_reviews to academic_teacher_profiles
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->unsignedInteger('total_reviews')->default(0)->after('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('total_reviews');
        });

        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('total_reviews');
        });
    }
};
