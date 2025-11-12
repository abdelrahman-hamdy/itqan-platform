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
        // Skip this migration if foreign keys already exist from schema dump
        // This migration was created after the schema dump and would create duplicates

        // Check if any foreign key already exists - if so, skip entire migration
        $foreignKeys = Schema::getForeignKeys('quran_sessions');
        if (count($foreignKeys) > 0) {
            // Foreign keys already exist from schema dump, skip migration
            return;
        }

        // If we get here, add the foreign keys
        // (This code will only run on fresh databases without schema dump)

        // Note: We're adding foreign keys with ON DELETE CASCADE or ON DELETE SET NULL
        // based on business logic requirements

        // QuranSessions foreign keys
        try {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                $table->foreign('quran_teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('quran_subscription_id')->references('id')->on('quran_subscriptions')->onDelete('set null');
                $table->foreign('circle_id')->references('id')->on('quran_circles')->onDelete('cascade');
                $table->foreign('individual_circle_id')->references('id')->on('quran_individual_circles')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys already exist
        }

        // QuranSubscriptions foreign keys
        try {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('quran_teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys already exist
        }

        // QuranCircles foreign keys
        try {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                $table->foreign('quran_teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys already exist
        }

        // QuranIndividualCircles foreign keys
        try {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                $table->foreign('quran_teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('subscription_id')->references('id')->on('quran_subscriptions')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys already exist
        }

        // AcademicSessions foreign keys
        if (Schema::hasTable('academic_sessions')) {
            try {
                Schema::table('academic_sessions', function (Blueprint $table) {
                    $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                    $table->foreign('academic_teacher_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('academic_subscription_id')->references('id')->on('academic_subscriptions')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Silently skip if foreign keys already exist
            }
        }

        // AcademicSubscriptions foreign keys
        if (Schema::hasTable('academic_subscriptions')) {
            try {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                    $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Silently skip if foreign keys already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys in reverse order
        if (Schema::hasTable('academic_subscriptions')) {
            try {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->dropForeign(['academy_id']);
                    $table->dropForeign(['student_id']);
                    $table->dropForeign(['teacher_id']);
                });
            } catch (\Exception $e) {
                // Silently skip if foreign keys don't exist
            }
        }

        if (Schema::hasTable('academic_sessions')) {
            try {
                Schema::table('academic_sessions', function (Blueprint $table) {
                    $table->dropForeign(['academy_id']);
                    $table->dropForeign(['academic_teacher_id']);
                    $table->dropForeign(['student_id']);
                    $table->dropForeign(['academic_subscription_id']);
                });
            } catch (\Exception $e) {
                // Silently skip if foreign keys don't exist
            }
        }

        try {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->dropForeign(['quran_teacher_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['subscription_id']);
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys don't exist
        }

        try {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->dropForeign(['quran_teacher_id']);
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys don't exist
        }

        try {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['quran_teacher_id']);
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys don't exist
        }

        try {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->dropForeign(['quran_teacher_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['quran_subscription_id']);
                $table->dropForeign(['circle_id']);
                $table->dropForeign(['individual_circle_id']);
            });
        } catch (\Exception $e) {
            // Silently skip if foreign keys don't exist
        }
    }
};
