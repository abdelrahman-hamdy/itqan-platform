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
        Schema::create('academic_grade_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('name'); // Arabic name (ابتدائي، متوسط، ثانوي، جامعي)
            $table->string('name_en')->nullable(); // English name
            $table->text('description')->nullable();
            $table->integer('level_number')->default(1); // Ordering level (1, 2, 3, etc.)
            $table->enum('education_system', ['primary', 'middle', 'secondary', 'university', 'vocational', 'international', 'special_needs'])->default('primary');
            $table->integer('age_group_min')->nullable(); // Minimum age for this level
            $table->integer('age_group_max')->nullable(); // Maximum age for this level
            $table->date('academic_year_start')->nullable();
            $table->date('academic_year_end')->nullable();
            $table->integer('total_subjects')->default(8);
            $table->integer('core_subjects_count')->default(6);
            $table->integer('elective_subjects_count')->default(2);
            $table->integer('total_credit_hours')->default(24);
            $table->integer('min_credit_hours')->default(18);
            $table->integer('max_credit_hours')->default(30);
            $table->json('graduation_requirements')->nullable(); // Array of requirements
            $table->enum('assessment_system', ['percentage', 'letter_grade', 'gpa', 'pass_fail', 'rubric'])->default('percentage');
            $table->json('grading_scale')->nullable(); // Custom grading scale
            $table->decimal('pass_percentage', 5, 2)->default(60.00);
            $table->text('curriculum_framework')->nullable();
            $table->json('learning_outcomes')->nullable(); // Expected learning outcomes
            $table->json('skill_requirements')->nullable(); // Required skills for this level
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(1);
            $table->string('color_code', 7)->default('#10B981'); // Hex color for UI
            $table->string('icon')->nullable(); // Icon class or path
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'is_active']);
            $table->index(['education_system', 'is_active']);
            $table->index(['level_number', 'display_order']);
            $table->index(['age_group_min', 'age_group_max']);

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_grade_levels');
    }
};
