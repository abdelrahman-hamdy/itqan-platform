<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update max_session_duration default from 90 to 60 for academic_teachers table
        Schema::table('academic_teachers', function (Blueprint $table) {
            $table->integer('max_session_duration')->default(60)->change();
        });

        // Update any existing records that have 90 minutes to use 60 minutes instead
        // This ensures consistency across the platform
        DB::table('academic_teachers')
            ->where('max_session_duration', 90)
            ->update(['max_session_duration' => 60]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the changes if needed
        Schema::table('academic_teachers', function (Blueprint $table) {
            $table->integer('max_session_duration')->default(90)->change();
        });
    }
};
