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
        Schema::table('academies', function (Blueprint $table) {
            $table->boolean('quran_show_circles')->default(true)->after('quran_show_in_nav');
            $table->boolean('quran_show_teachers')->default(true)->after('quran_show_circles');
            $table->boolean('academic_show_courses')->default(true)->after('academic_show_in_nav');
            $table->boolean('academic_show_teachers')->default(true)->after('academic_show_courses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn(['quran_show_circles', 'quran_show_teachers', 'academic_show_courses', 'academic_show_teachers']);
        });
    }
};
