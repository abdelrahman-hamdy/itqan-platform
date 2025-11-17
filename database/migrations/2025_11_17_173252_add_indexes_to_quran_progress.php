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
        Schema::table('quran_progress', function (Blueprint $table) {
            // Add indexes for optimizing report queries
            $table->index(['student_id', 'progress_date'], 'quran_progress_student_date_index');
            $table->index(['circle_id', 'student_id'], 'quran_progress_circle_student_index');
            $table->index('session_id', 'quran_progress_session_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_progress', function (Blueprint $table) {
            $table->dropIndex('quran_progress_student_date_index');
            $table->dropIndex('quran_progress_circle_student_index');
            $table->dropIndex('quran_progress_session_index');
        });
    }
};
