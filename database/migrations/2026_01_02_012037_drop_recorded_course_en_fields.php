<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop English fields and unused audit fields from recorded_courses table.
 * Note: This table already uses simple names (title, description), just dropping _en versions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop columns (no foreign keys exist on this table)
        Schema::table('recorded_courses', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('recorded_courses', 'title_en')) {
                $columnsToDrop[] = 'title_en';
            }
            if (Schema::hasColumn('recorded_courses', 'description_en')) {
                $columnsToDrop[] = 'description_en';
            }
            if (Schema::hasColumn('recorded_courses', 'created_by')) {
                $columnsToDrop[] = 'created_by';
            }
            if (Schema::hasColumn('recorded_courses', 'updated_by')) {
                $columnsToDrop[] = 'updated_by';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            if (! Schema::hasColumn('recorded_courses', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (! Schema::hasColumn('recorded_courses', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
            if (! Schema::hasColumn('recorded_courses', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (! Schema::hasColumn('recorded_courses', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
