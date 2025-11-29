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
        Schema::create('academic_subject_grade_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('academic_subjects')->onDelete('cascade');
            $table->foreignId('grade_level_id')->constrained('academic_grade_levels')->onDelete('cascade');
            $table->integer('hours_per_week')->default(3);
            $table->enum('semester', ['first', 'second', 'both', 'summer'])->default('both');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            // Ensure unique subject-grade combinations
            $table->unique(['subject_id', 'grade_level_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_subject_grade_levels');
    }
};
