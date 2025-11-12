<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restore session_duration_minutes to quran_circles table.
     * GROUP circles need this field as all students follow the same pre-configured duration.
     * (Individual circles get duration from their subscription package)
     */
    public function up(): void
    {
        if (Schema::hasTable('quran_circles') && !Schema::hasColumn('quran_circles', 'session_duration_minutes')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->integer('session_duration_minutes')
                    ->default(60)
                    ->after('monthly_sessions_count')
                    ->comment('Duration in minutes for GROUP circle sessions');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quran_circles') && Schema::hasColumn('quran_circles', 'session_duration_minutes')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->dropColumn('session_duration_minutes');
            });
        }
    }
};
