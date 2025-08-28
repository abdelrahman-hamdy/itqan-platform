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
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Add the missing index with a shorter name
            $table->index(['preparation_minutes', 'ending_buffer_minutes'], 'qi_circles_meeting_config_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropIndex('qi_circles_meeting_config_idx');
        });
    }
};