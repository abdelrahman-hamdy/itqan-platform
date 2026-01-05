<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop English fields and unused audit fields from course_sections and lessons tables.
 * Note: These tables already use simple names (title, description), just dropping _en versions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Course Sections - Drop columns (no foreign keys exist on these tables)
        Schema::table('course_sections', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('course_sections', 'title_en')) {
                $columnsToDrop[] = 'title_en';
            }
            if (Schema::hasColumn('course_sections', 'description_en')) {
                $columnsToDrop[] = 'description_en';
            }
            if (Schema::hasColumn('course_sections', 'created_by')) {
                $columnsToDrop[] = 'created_by';
            }
            if (Schema::hasColumn('course_sections', 'updated_by')) {
                $columnsToDrop[] = 'updated_by';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Lessons - Drop columns (no foreign keys exist on these tables)
        Schema::table('lessons', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('lessons', 'title_en')) {
                $columnsToDrop[] = 'title_en';
            }
            if (Schema::hasColumn('lessons', 'description_en')) {
                $columnsToDrop[] = 'description_en';
            }
            if (Schema::hasColumn('lessons', 'created_by')) {
                $columnsToDrop[] = 'created_by';
            }
            if (Schema::hasColumn('lessons', 'updated_by')) {
                $columnsToDrop[] = 'updated_by';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        // Course Sections - Re-add columns
        Schema::table('course_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('course_sections', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (!Schema::hasColumn('course_sections', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('course_sections', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('course_sections', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });

        // Lessons - Re-add columns
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (!Schema::hasColumn('lessons', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('lessons', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('lessons', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
