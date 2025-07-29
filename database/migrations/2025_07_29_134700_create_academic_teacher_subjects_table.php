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
        Schema::create('academic_teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id'); // References academic_teachers.id
            $table->unsignedBigInteger('subject_id'); // References subjects.id
            
            // Additional pivot data
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->integer('years_experience')->default(0); // Years teaching this subject
            $table->boolean('is_primary')->default(false); // Is this a primary subject for the teacher
            $table->json('certification')->nullable(); // Certifications for this subject
            
            $table->timestamps();

            // Indexes
            $table->index(['teacher_id', 'subject_id'], 'at_subj_teacher_subj_idx');
            $table->index(['subject_id', 'proficiency_level'], 'at_subj_level_idx');
            $table->index(['is_primary', 'proficiency_level'], 'at_subj_primary_level_idx');
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['teacher_id', 'subject_id'], 'at_subj_teacher_subj_unique');

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_teacher_subjects');
    }
};
