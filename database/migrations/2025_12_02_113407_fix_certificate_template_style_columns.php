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
        // Fix interactive_courses table
        DB::statement("ALTER TABLE interactive_courses MODIFY COLUMN certificate_template_style VARCHAR(50) DEFAULT 'template_1'");

        // Update existing 'modern' values to 'template_1'
        DB::table('interactive_courses')
            ->where('certificate_template_style', 'modern')
            ->update(['certificate_template_style' => 'template_1']);

        // Update existing 'classic' values to 'template_2'
        DB::table('interactive_courses')
            ->where('certificate_template_style', 'classic')
            ->update(['certificate_template_style' => 'template_2']);

        // Update existing 'elegant' values to 'template_3'
        DB::table('interactive_courses')
            ->where('certificate_template_style', 'elegant')
            ->update(['certificate_template_style' => 'template_3']);

        // Fix recorded_courses table
        DB::statement("ALTER TABLE recorded_courses MODIFY COLUMN certificate_template_style VARCHAR(50) DEFAULT 'template_1'");

        // Update existing 'modern' values to 'template_1'
        DB::table('recorded_courses')
            ->where('certificate_template_style', 'modern')
            ->update(['certificate_template_style' => 'template_1']);

        // Update existing 'classic' values to 'template_2'
        DB::table('recorded_courses')
            ->where('certificate_template_style', 'classic')
            ->update(['certificate_template_style' => 'template_2']);

        // Update existing 'elegant' values to 'template_3'
        DB::table('recorded_courses')
            ->where('certificate_template_style', 'elegant')
            ->update(['certificate_template_style' => 'template_3']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes - convert back to ENUM
        DB::statement("ALTER TABLE interactive_courses MODIFY COLUMN certificate_template_style ENUM('modern', 'classic', 'elegant') DEFAULT 'modern'");
        DB::statement("ALTER TABLE recorded_courses MODIFY COLUMN certificate_template_style ENUM('modern', 'classic', 'elegant') DEFAULT 'modern'");
    }
};
