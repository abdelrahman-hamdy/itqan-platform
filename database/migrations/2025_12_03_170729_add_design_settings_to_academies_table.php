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
            // Sections order as JSON array
            $table->json('sections_order')->nullable();

            // Hero Section Settings
            $table->boolean('hero_visible')->default(true);
            $table->string('hero_template')->default('template_1');
            $table->string('hero_heading')->nullable();
            $table->boolean('hero_show_in_nav')->default(false);

            // Stats Section Settings
            $table->boolean('stats_visible')->default(true);
            $table->string('stats_template')->default('template_1');
            $table->string('stats_heading')->nullable()->default('إنجازاتنا بالأرقام');
            $table->boolean('stats_show_in_nav')->default(true);

            // Reviews Section Settings
            $table->boolean('reviews_visible')->default(true);
            $table->string('reviews_template')->default('template_1');
            $table->string('reviews_heading')->nullable()->default('آراء طلابنا');
            $table->boolean('reviews_show_in_nav')->default(true);

            // Quran Section Settings
            $table->boolean('quran_visible')->default(true);
            $table->string('quran_template')->default('template_1');
            $table->string('quran_heading')->nullable()->default('برامج القرآن الكريم');
            $table->boolean('quran_show_in_nav')->default(true);

            // Academic Section Settings
            $table->boolean('academic_visible')->default(true);
            $table->string('academic_template')->default('template_1');
            $table->string('academic_heading')->nullable()->default('البرامج الأكاديمية');
            $table->boolean('academic_show_in_nav')->default(true);

            // Courses Section Settings
            $table->boolean('courses_visible')->default(true);
            $table->string('courses_template')->default('template_1');
            $table->string('courses_heading')->nullable()->default('الدورات المسجلة');
            $table->boolean('courses_show_in_nav')->default(true);

            // Features Section Settings
            $table->boolean('features_visible')->default(true);
            $table->string('features_template')->default('template_1');
            $table->string('features_heading')->nullable()->default('مميزات المنصة');
            $table->boolean('features_show_in_nav')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn([
                'sections_order',
                'hero_visible', 'hero_template', 'hero_heading', 'hero_show_in_nav',
                'stats_visible', 'stats_template', 'stats_heading', 'stats_show_in_nav',
                'reviews_visible', 'reviews_template', 'reviews_heading', 'reviews_show_in_nav',
                'quran_visible', 'quran_template', 'quran_heading', 'quran_show_in_nav',
                'academic_visible', 'academic_template', 'academic_heading', 'academic_show_in_nav',
                'courses_visible', 'courses_template', 'courses_heading', 'courses_show_in_nav',
                'features_visible', 'features_template', 'features_heading', 'features_show_in_nav',
            ]);
        });
    }
};
