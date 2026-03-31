<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->json('individual_session_prices')->nullable()->after('session_price_group')
                ->comment('JSON map of duration_minutes => price, e.g. {"60": 65.00}');
            $table->json('group_session_prices')->nullable()->after('individual_session_prices')
                ->comment('JSON map of duration_minutes => price for group sessions');
        });

        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->json('individual_session_prices')->nullable()->after('session_price_individual')
                ->comment('JSON map of duration_minutes => price, e.g. {"60": 80.00}');
        });

        // Migrate existing flat rates to the 60-minute slot in the new JSON columns
        DB::statement("
            UPDATE quran_teacher_profiles
            SET individual_session_prices = JSON_OBJECT('60', session_price_individual)
            WHERE session_price_individual IS NOT NULL AND session_price_individual > 0
        ");

        DB::statement("
            UPDATE quran_teacher_profiles
            SET group_session_prices = JSON_OBJECT('60', session_price_group)
            WHERE session_price_group IS NOT NULL AND session_price_group > 0
        ");

        DB::statement("
            UPDATE academic_teacher_profiles
            SET individual_session_prices = JSON_OBJECT('60', session_price_individual)
            WHERE session_price_individual IS NOT NULL AND session_price_individual > 0
        ");
    }

    public function down(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['individual_session_prices', 'group_session_prices']);
        });

        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('individual_session_prices');
        });
    }
};
