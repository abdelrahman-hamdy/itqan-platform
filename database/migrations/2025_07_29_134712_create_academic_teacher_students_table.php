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
        Schema::create('academic_teacher_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id'); // References academic_teachers.id
            $table->unsignedBigInteger('student_id'); // References users.id (where role = 'student')
            
            // Additional pivot data
            $table->date('start_date'); // When the teacher-student relationship started
            $table->date('end_date')->nullable(); // When it ended (if applicable)
            $table->enum('status', ['active', 'completed', 'suspended', 'cancelled'])->default('active');
            $table->json('current_subjects')->nullable(); // Currently studying subjects
            $table->decimal('performance_rating', 3, 2)->nullable(); // Teacher's rating of student (0.00-5.00)
            
            $table->timestamps();

            // Indexes
            $table->index(['teacher_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index(['end_date']);
            
            // Unique constraint to prevent duplicate active relationships
            $table->unique(['teacher_id', 'student_id']);

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_teacher_students');
    }
};
