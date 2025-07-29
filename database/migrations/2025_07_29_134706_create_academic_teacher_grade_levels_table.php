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
        Schema::create('academic_teacher_grade_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id'); // References academic_teachers.id
            $table->unsignedBigInteger('grade_level_id'); // References grade_levels.id
            
            // Additional pivot data
            $table->integer('years_experience')->default(0); // Years teaching this grade level
            $table->text('specialization_notes')->nullable(); // Special notes about teaching this grade
            
            $table->timestamps();

            // Indexes
            $table->index(['teacher_id', 'grade_level_id'], 'at_grade_teacher_grade_idx');
            $table->index(['grade_level_id', 'years_experience'], 'at_grade_level_exp_idx');
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['teacher_id', 'grade_level_id'], 'at_grade_teacher_grade_unique');

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_teacher_grade_levels');
    }
};
