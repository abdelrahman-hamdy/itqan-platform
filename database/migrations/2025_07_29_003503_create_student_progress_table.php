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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('recorded_course_id');
            $table->unsignedBigInteger('course_section_id')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            
            // Progress Type
            $table->enum('progress_type', ['course', 'section', 'lesson'])->default('lesson');
            
            // Progress Data
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('watch_time_seconds')->default(0);
            $table->integer('total_time_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            
            // Video Progress
            $table->integer('current_position_seconds')->default(0);
            
            // Quiz Progress
            $table->decimal('quiz_score', 5, 2)->nullable();
            $table->integer('quiz_attempts')->default(0);
            
            // Student Notes and Bookmarks
            $table->json('notes')->nullable();
            $table->timestamp('bookmarked_at')->nullable();
            
            // Student Rating and Review
            $table->integer('rating')->nullable();
            $table->text('review_text')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'recorded_course_id']);
            $table->index(['user_id', 'lesson_id']);
            $table->index(['recorded_course_id', 'is_completed']);
            $table->index(['progress_type', 'is_completed']);
            $table->index(['last_accessed_at']);
            
            // Unique constraints
            $table->unique(['user_id', 'recorded_course_id', 'lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
