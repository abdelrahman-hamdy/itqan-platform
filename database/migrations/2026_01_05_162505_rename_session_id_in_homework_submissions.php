<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename session_id to interactive_course_session_id for consistency
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactive_course_homework_submissions', function (Blueprint $table) {
            // Rename column for consistency with other tables
            $table->renameColumn('session_id', 'interactive_course_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('interactive_course_homework_submissions', function (Blueprint $table) {
            $table->renameColumn('interactive_course_session_id', 'session_id');
        });
    }
};
