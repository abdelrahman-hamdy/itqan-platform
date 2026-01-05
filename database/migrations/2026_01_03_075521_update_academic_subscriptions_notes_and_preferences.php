<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Standardize notes columns to match the pattern used across the platform:
     * - notes → admin_notes (internal admin notes)
     * - teacher_notes → supervisor_notes (supervisor/management notes)
     * - Add learning_goals for student preferences
     * - Add preferred_times for scheduling preferences
     */
    public function up(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Rename notes to admin_notes
            $table->renameColumn('notes', 'admin_notes');

            // Rename teacher_notes to supervisor_notes
            $table->renameColumn('teacher_notes', 'supervisor_notes');
        });

        // Add new columns in a separate statement to avoid issues
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Add learning_goals for student preferences
            $table->text('learning_goals')->nullable()->after('student_notes');

            // Add preferred_times for scheduling preferences (array of preferred times)
            $table->json('preferred_times')->nullable()->after('learning_goals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Drop new columns first
            $table->dropColumn(['learning_goals', 'preferred_times']);
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Revert column renames
            $table->renameColumn('admin_notes', 'notes');
            $table->renameColumn('supervisor_notes', 'teacher_notes');
        });
    }
};
