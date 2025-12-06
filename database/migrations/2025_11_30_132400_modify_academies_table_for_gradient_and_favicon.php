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
            // Add new fields
            $table->string('gradient_palette')->default('ocean_breeze')->after('brand_color');
            $table->string('favicon')->nullable()->after('logo');

            // Remove old fields
            $table->dropColumn('secondary_color');
            $table->dropColumn('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            // Restore old fields
            $table->string('secondary_color')->default('emerald')->after('brand_color');
            $table->string('theme')->default('light')->after('gradient_palette');

            // Remove new fields
            $table->dropColumn('gradient_palette');
            $table->dropColumn('favicon');
        });
    }
};
