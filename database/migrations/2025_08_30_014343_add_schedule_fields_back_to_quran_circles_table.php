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
        Schema::table('quran_circles', function (Blueprint $table) {
            // Add back schedule fields for basic scheduling support
            // These will be used alongside the QuranCircleSchedule system
            $table->json('schedule_days')->nullable()->after('schedule_configured_by')
                ->comment('Basic weekdays for display - JSON array of weekday strings');

            $table->time('schedule_time')->nullable()->after('schedule_days')
                ->comment('Basic time for display - time format HH:MM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn(['schedule_days', 'schedule_time']);
        });
    }
};
