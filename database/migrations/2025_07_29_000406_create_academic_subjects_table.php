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
        Schema::create('academic_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('name'); // Arabic name
            $table->string('name_en')->nullable(); // English name
            $table->text('description')->nullable();
            $table->enum('category', ['sciences', 'mathematics', 'languages', 'humanities', 'social_studies', 'arts', 'technology', 'physical_education', 'religious_studies', 'vocational'])->default('sciences');
            $table->enum('field', ['natural_sciences', 'applied_sciences', 'formal_sciences', 'humanities', 'social_sciences', 'interdisciplinary'])->default('natural_sciences');
            $table->json('level_scope')->nullable(); // Which grade levels this subject covers
            $table->json('prerequisites')->nullable(); // Subject IDs that must be completed first
            $table->string('color_code', 7)->default('#3B82F6'); // Hex color for UI
            $table->string('icon')->nullable(); // Icon class or path
            $table->boolean('is_core_subject')->default(true);
            $table->boolean('is_elective')->default(false);
            $table->integer('credit_hours')->default(3);
            $table->integer('difficulty_level')->default(1); // 1-5 scale
            $table->integer('estimated_duration_weeks')->default(16);
            $table->text('curriculum_framework')->nullable();
            $table->json('learning_objectives')->nullable(); // Array of learning objectives
            $table->json('assessment_methods')->nullable(); // ['written_exam', 'oral_exam', 'practical_exam', 'project', etc.]
            $table->json('required_materials')->nullable(); // Books, tools, software, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->index(['field', 'is_active']);
            $table->index(['difficulty_level', 'is_active']);
            $table->index(['is_core_subject', 'is_elective']);
            $table->index('display_order');

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_subjects');
    }
};
