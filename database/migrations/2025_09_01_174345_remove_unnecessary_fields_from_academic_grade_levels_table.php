<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_grade_levels', function (Blueprint $table) {
            // Remove fields from "تفاصيل الصف" section
            $table->dropColumn([
                'level_number',
                'education_system',
                'academic_year_start',
                'academic_year_end',
                'display_order',
            ]);

            // Remove fields from "متطلبات المواد والدرجات" section
            $table->dropColumn([
                'total_subjects',
                'core_subjects_count',
                'elective_subjects_count',
                'total_credit_hours',
                'min_credit_hours',
                'max_credit_hours',
                'graduation_requirements',
                'assessment_system',
                'grading_scale',
                'pass_percentage',
                'curriculum_framework',
                'learning_outcomes',
                'skill_requirements',
            ]);

            // Remove icon field
            $table->dropColumn('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_grade_levels', function (Blueprint $table) {
            // Re-add fields from "تفاصيل الصف" section
            $table->integer('level_number')->default(1)->after('description');
            $table->enum('education_system', ['primary', 'middle', 'secondary', 'university', 'vocational', 'international', 'special_needs'])->default('primary')->after('level_number');
            $table->date('academic_year_start')->nullable()->after('education_system');
            $table->date('academic_year_end')->nullable()->after('academic_year_start');
            $table->integer('display_order')->default(1)->after('academic_year_end');

            // Re-add fields from "متطلبات المواد والدرجات" section
            $table->integer('total_subjects')->default(8)->after('display_order');
            $table->integer('core_subjects_count')->default(6)->after('total_subjects');
            $table->integer('elective_subjects_count')->default(2)->after('core_subjects_count');
            $table->integer('total_credit_hours')->default(24)->after('elective_subjects_count');
            $table->integer('min_credit_hours')->default(18)->after('total_credit_hours');
            $table->integer('max_credit_hours')->default(30)->after('min_credit_hours');
            $table->json('graduation_requirements')->nullable()->after('max_credit_hours');
            $table->enum('assessment_system', ['percentage', 'letter_grade', 'gpa', 'pass_fail', 'rubric'])->default('percentage')->after('graduation_requirements');
            $table->json('grading_scale')->nullable()->after('assessment_system');
            $table->decimal('pass_percentage', 5, 2)->default(60.00)->after('grading_scale');
            $table->text('curriculum_framework')->nullable()->after('pass_percentage');
            $table->json('learning_outcomes')->nullable()->after('curriculum_framework');
            $table->json('skill_requirements')->nullable()->after('learning_outcomes');

            // Re-add icon field
            $table->string('icon')->nullable()->after('color_code');
        });
    }
};
