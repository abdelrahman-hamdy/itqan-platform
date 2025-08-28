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
            // Meeting preparation time before session start (minutes)
            $table->integer('preparation_minutes')->default(15)->comment('Minutes before session to prepare meeting');
            
            // Buffer time after session ends before auto-terminating meeting (minutes)
            $table->integer('ending_buffer_minutes')->default(5)->comment('Minutes after session ends to keep meeting active');
            
            // Grace period for students to join late (minutes)
            $table->integer('late_join_grace_period_minutes')->default(15)->comment('Minutes students can join late without penalty');
            
            // Index for better query performance (with custom name to avoid length issues)
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
            $table->dropColumn([
                'preparation_minutes',
                'ending_buffer_minutes', 
                'late_join_grace_period_minutes'
            ]);
        });
    }
};