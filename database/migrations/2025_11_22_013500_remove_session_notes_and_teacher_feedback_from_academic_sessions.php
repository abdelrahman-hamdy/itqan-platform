<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove deprecated session_notes and teacher_feedback fields from academic_sessions.
     * These fields are now handled through the AcademicSessionReport model instead.
     */
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn(['session_notes', 'teacher_feedback']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->text('session_notes')->nullable()->after('recording_enabled');
            $table->text('teacher_feedback')->nullable()->after('session_notes');
        });
    }
};
