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
        // Check if the column exists before trying to remove it
        if (Schema::hasColumn('quran_sessions', 'counts_toward_subscription')) {
            // Just drop the column - indexes will be dropped automatically
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->dropColumn('counts_toward_subscription');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            //
        });
    }
};
