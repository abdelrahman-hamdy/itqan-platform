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
        Schema::create('quran_circle_students', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('circle_id')->constrained('quran_circles')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            
            // Enrollment Details
            $table->timestamp('enrolled_at');
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'suspended', 'transferred'])->default('enrolled');
            
            // Progress Tracking
            $table->integer('attendance_count')->default(0);
            $table->integer('missed_sessions')->default(0);
            $table->integer('makeup_sessions_used')->default(0);
            $table->enum('current_level', ['beginner', 'elementary', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->text('progress_notes')->nullable();
            
            // Ratings
            $table->integer('parent_rating')->nullable()->comment('1-5 rating from parent');
            $table->integer('student_rating')->nullable()->comment('1-5 rating from student');
            
            // Completion
            $table->timestamp('completion_date')->nullable();
            $table->boolean('certificate_issued')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['circle_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['status', 'enrolled_at']);
            $table->index('certificate_issued');
            
            // Unique Constraints
            $table->unique(['circle_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_circle_students');
    }
};
