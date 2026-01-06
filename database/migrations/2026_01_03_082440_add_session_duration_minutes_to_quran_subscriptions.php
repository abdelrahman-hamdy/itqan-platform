<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add session_duration_minutes column for direct editing in admin panel.
     * Copy existing data from package_session_duration_minutes.
     */
    public function up(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('session_duration_minutes')
                ->default(45)
                ->after('total_sessions_missed');
        });

        // Copy data from package_session_duration_minutes to session_duration_minutes
        DB::table('quran_subscriptions')
            ->whereNotNull('package_session_duration_minutes')
            ->update([
                'session_duration_minutes' => DB::raw('package_session_duration_minutes'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropColumn('session_duration_minutes');
        });
    }
};
