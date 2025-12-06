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
            $table->text('hero_subheading')->nullable()->after('hero_heading');
            $table->text('stats_subheading')->nullable()->after('stats_heading');
            $table->text('reviews_subheading')->nullable()->after('reviews_heading');
            $table->text('quran_subheading')->nullable()->after('quran_heading');
            $table->text('academic_subheading')->nullable()->after('academic_heading');
            $table->text('courses_subheading')->nullable()->after('courses_heading');
            $table->text('features_subheading')->nullable()->after('features_heading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn([
                'hero_subheading',
                'stats_subheading',
                'reviews_subheading',
                'quran_subheading',
                'academic_subheading',
                'courses_subheading',
                'features_subheading',
            ]);
        });
    }
};
