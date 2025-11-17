<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove all verse-related columns from Quran tables.
     * The system now uses pages-only measurement.
     */
    public function up(): void
    {
        // Drop verse columns from quran_subscriptions
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('quran_subscriptions', 'current_verse')) {
                $table->dropColumn('current_verse');
            }
            if (Schema::hasColumn('quran_subscriptions', 'verses_memorized')) {
                $table->dropColumn('verses_memorized');
            }
        });

        // Drop verse columns from quran_individual_circles
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            if (Schema::hasColumn('quran_individual_circles', 'verses_memorized')) {
                $table->dropColumn('verses_memorized');
            }
        });

        // Note: quran_progress table keeps all fields as it's the comprehensive tracking model
        // but new code should only write to pages fields
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore verse columns
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->integer('current_verse')->nullable()->after('current_surah');
            $table->integer('verses_memorized')->default(0)->after('current_verse');
        });

        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->integer('verses_memorized')->default(0)->after('papers_memorized_precise');
        });
    }
};
