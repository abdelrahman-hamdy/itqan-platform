<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename notes to admin_notes and drop teacher_notes to match Quran section pattern.
     * The supervisor_notes column already exists and will be kept.
     */
    public function up(): void
    {
        Schema::table('academic_individual_lessons', function (Blueprint $table) {
            // Rename notes to admin_notes for consistency with Quran section
            $table->renameColumn('notes', 'admin_notes');
        });

        // Drop teacher_notes in separate statement (required by some DB drivers)
        Schema::table('academic_individual_lessons', function (Blueprint $table) {
            $table->dropColumn('teacher_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_individual_lessons', function (Blueprint $table) {
            // Restore teacher_notes column
            $table->text('teacher_notes')->nullable()->after('supervisor_notes');
        });

        Schema::table('academic_individual_lessons', function (Blueprint $table) {
            // Rename admin_notes back to notes
            $table->renameColumn('admin_notes', 'notes');
        });
    }
};
