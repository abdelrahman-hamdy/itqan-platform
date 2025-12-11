<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need soft deletes added.
     * These are critical tables where data should never be permanently deleted.
     */
    private array $tablesNeedingSoftDeletes = [
        'users',
        'academies',
        'student_profiles',
        'parent_profiles',
        'quran_teacher_profiles',
        'academic_teacher_profiles',
        'interactive_courses',
        'quran_subscriptions',
        'homework_submissions',
        'quiz_assignments',
        'quiz_attempts',
        'grade_levels',
        'academic_subjects',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tablesNeedingSoftDeletes as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tablesNeedingSoftDeletes as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
