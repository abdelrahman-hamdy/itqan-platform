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
        // Add certificate settings to recorded_courses
        Schema::table('recorded_courses', function (Blueprint $table) {
            $table->text('certificate_template_text')->nullable()->after('description_en');
            $table->enum('certificate_template_style', ['modern', 'classic', 'elegant'])->default('modern')->after('certificate_template_text');
        });

        // Add certificate settings to interactive_courses
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->boolean('certificate_enabled')->default(true)->after('status');
            $table->enum('certificate_template_style', ['modern', 'classic', 'elegant'])->default('modern')->after('certificate_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            $table->dropColumn(['certificate_template_text', 'certificate_template_style']);
        });

        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn(['certificate_enabled', 'certificate_template_style']);
        });
    }
};
