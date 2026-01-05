<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * BREAKING CHANGE: Removes current_surah and current_page columns
     * These fields were not used in any active feature.
     * Progress tracking now uses the sessionHomework relationship instead.
     */
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropColumn(['current_surah', 'current_page']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->string('current_surah')->nullable()->after('session_month');
            $table->integer('current_page')->nullable()->after('current_surah');
        });
    }
};
