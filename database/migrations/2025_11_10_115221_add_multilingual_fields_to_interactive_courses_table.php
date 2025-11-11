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
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title')
                ->comment('English title for the course');

            $table->text('description_en')->nullable()->after('description')
                ->comment('English description for the course');

            $table->enum('language', ['ar', 'en', 'both'])->default('ar')->after('description_en')
                ->comment('Language of instruction: ar = Arabic, en = English, both = Bilingual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en', 'language']);
        });
    }
};
