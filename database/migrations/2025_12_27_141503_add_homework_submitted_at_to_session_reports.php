<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add homework_submitted_at field to all session report tables.
 * This field tracks when homework was submitted for a specific session.
 *
 * Also adds homework_submission_id to link reports directly to submissions
 * for better data integrity and sync capabilities.
 */
return new class extends Migration
{
    /**
     * The session report tables that need the homework fields
     */
    private array $reportTables = [
        'student_session_reports',      // Quran session reports
        'academic_session_reports',     // Academic session reports
        'interactive_session_reports',  // Interactive course session reports
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->reportTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Add homework_submitted_at to track when homework was submitted
                    if (! Schema::hasColumn($tableName, 'homework_submitted_at')) {
                        $table->timestamp('homework_submitted_at')->nullable();
                    }

                    // Add homework_submission_id to link to HomeworkSubmission
                    if (! Schema::hasColumn($tableName, 'homework_submission_id')) {
                        $table->unsignedBigInteger('homework_submission_id')->nullable();

                        // Add foreign key constraint
                        $table->foreign('homework_submission_id')
                            ->references('id')
                            ->on('homework_submissions')
                            ->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->reportTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Drop foreign key first, then the column
                    if (Schema::hasColumn($tableName, 'homework_submission_id')) {
                        // Generate foreign key name based on Laravel convention
                        $foreignKeyName = $tableName.'_homework_submission_id_foreign';
                        $table->dropForeign($foreignKeyName);
                        $table->dropColumn('homework_submission_id');
                    }

                    if (Schema::hasColumn($tableName, 'homework_submitted_at')) {
                        $table->dropColumn('homework_submitted_at');
                    }
                });
            }
        }
    }
};
