<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused/orphaned columns from interactive_courses table.
 *
 * - title_en/description_en: No bilingual support implemented
 * - enrollment_fee/is_enrollment_fee_required: Fields exist but never used
 * - course_type: Superseded by course_type_in_arabic accessor
 * - created_by/updated_by: Never read in application
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints if they exist
        Schema::table('interactive_courses', function (Blueprint $table) {
            $foreignKeys = [
                'interactive_courses_created_by_foreign',
                'interactive_courses_updated_by_foreign',
            ];

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }
        });

        // Now drop the columns
        Schema::table('interactive_courses', function (Blueprint $table) {
            $columnsToDrop = [
                'title_en',                   // No bilingual support implemented
                'description_en',             // No bilingual support implemented
                'enrollment_fee',             // Field exists but never used
                'is_enrollment_fee_required', // Field exists but never used
                'course_type',                // Superseded by course_type_in_arabic
                'created_by',                 // Never read in application
                'updated_by',                 // Never read in application
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('interactive_courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            if (!Schema::hasColumn('interactive_courses', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (!Schema::hasColumn('interactive_courses', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('interactive_courses', 'enrollment_fee')) {
                $table->decimal('enrollment_fee', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('interactive_courses', 'is_enrollment_fee_required')) {
                $table->boolean('is_enrollment_fee_required')->default(false);
            }
            if (!Schema::hasColumn('interactive_courses', 'course_type')) {
                $table->string('course_type')->nullable();
            }
            if (!Schema::hasColumn('interactive_courses', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('interactive_courses', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
