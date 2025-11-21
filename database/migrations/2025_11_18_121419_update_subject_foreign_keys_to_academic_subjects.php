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
        // Helper function to check if foreign key exists
        $foreignKeyExists = function($table, $column) {
            $result = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                AND CONSTRAINT_NAME LIKE ?',
                [env('DB_DATABASE'), $table, $column, '%_foreign']
            );
            return !empty($result);
        };

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Update interactive_courses table
            if (Schema::hasTable('interactive_courses')) {
                Schema::table('interactive_courses', function (Blueprint $table) use ($foreignKeyExists) {
                    // Drop the old foreign key constraint if it exists
                    if ($foreignKeyExists('interactive_courses', 'subject_id')) {
                        $table->dropForeign(['subject_id']);
                    }

                    // Add new foreign key constraint referencing academic_subjects
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('academic_subjects')
                        ->onDelete('cascade');
                });
            }

            // Update recorded_courses table
            if (Schema::hasTable('recorded_courses') && Schema::hasColumn('recorded_courses', 'subject_id')) {
                Schema::table('recorded_courses', function (Blueprint $table) use ($foreignKeyExists) {
                    // Drop the old foreign key constraint if it exists
                    if ($foreignKeyExists('recorded_courses', 'subject_id')) {
                        $table->dropForeign(['subject_id']);
                    }

                    // Add new foreign key constraint referencing academic_subjects
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('academic_subjects')
                        ->onDelete('cascade');
                });
            }

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Revert interactive_courses table
            if (Schema::hasTable('interactive_courses')) {
                Schema::table('interactive_courses', function (Blueprint $table) {
                    // Drop the academic_subjects foreign key
                    $table->dropForeign(['subject_id']);

                    // Add back the subjects foreign key (if subjects table exists)
                    if (Schema::hasTable('subjects')) {
                        $table->foreign('subject_id')
                            ->references('id')
                            ->on('subjects')
                            ->onDelete('cascade');
                    }
                });
            }

            // Revert recorded_courses table
            if (Schema::hasTable('recorded_courses')) {
                Schema::table('recorded_courses', function (Blueprint $table) {
                    // Drop the academic_subjects foreign key
                    $table->dropForeign(['subject_id']);

                    // Add back the subjects foreign key (if subjects table exists)
                    if (Schema::hasTable('subjects')) {
                        $table->foreign('subject_id')
                            ->references('id')
                            ->on('subjects')
                            ->onDelete('cascade');
                    }
                });
            }

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
