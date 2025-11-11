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
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Change the status enum to include 'unscheduled' and other missing values
            $table->enum('status', [
                'unscheduled',
                'scheduled', 
                'ready',
                'ongoing', 
                'completed', 
                'cancelled', 
                'absent',
                'missed',
                'rescheduled'
            ])->default('unscheduled')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('status', [
                'scheduled',
                'ongoing',
                'completed',
                'cancelled',
                'rescheduled'
            ])->default('scheduled')->change();
        });
    }
};