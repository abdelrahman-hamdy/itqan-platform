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
        // Remove meeting configuration fields from quran_circles table
        Schema::table('quran_circles', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['preparation_minutes', 'ending_buffer_minutes']);
            
            // Drop the columns
            $table->dropColumn([
                'preparation_minutes',
                'ending_buffer_minutes', 
                'late_join_grace_period_minutes'
            ]);
        });

        // Remove meeting configuration fields from quran_individual_circles table
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Drop the index with custom name
            $table->dropIndex('qi_circles_meeting_config_idx');
            
            // Drop the columns
            $table->dropColumn([
                'preparation_minutes',
                'ending_buffer_minutes', 
                'late_join_grace_period_minutes'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back meeting configuration fields to quran_circles table
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->integer('preparation_minutes')->default(15)->comment('Minutes before session to prepare meeting');
            $table->integer('ending_buffer_minutes')->default(5)->comment('Minutes after session ends to keep meeting active');
            $table->integer('late_join_grace_period_minutes')->default(15)->comment('Minutes students can join late without penalty');
            $table->index(['preparation_minutes', 'ending_buffer_minutes']);
        });

        // Add back meeting configuration fields to quran_individual_circles table
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->integer('preparation_minutes')->default(15)->comment('Minutes before session to prepare meeting');
            $table->integer('ending_buffer_minutes')->default(5)->comment('Minutes after session ends to keep meeting active');
            $table->integer('late_join_grace_period_minutes')->default(15)->comment('Minutes students can join late without penalty');
            $table->index(['preparation_minutes', 'ending_buffer_minutes'], 'qi_circles_meeting_config_idx');
        });
    }
};
