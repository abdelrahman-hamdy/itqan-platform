<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Add admin_notes to quran_circles for consistency with individual circles
     * 2. Drop materials_used from quran_individual_circles (not needed)
     */
    public function up(): void
    {
        // Add admin_notes to quran_circles (beside supervisor_notes)
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('supervisor_notes');
        });

        // Drop materials_used from quran_individual_circles
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropColumn('materials_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });

        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->json('materials_used')->nullable();
        });
    }
};
