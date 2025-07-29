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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recorded_course_id');
            $table->unsignedBigInteger('course_section_id');
            
            // Lesson Information
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('lesson_code')->unique();
            
            // Video Information
            $table->string('video_url');
            $table->integer('video_duration_seconds')->default(0);
            $table->decimal('video_size_mb', 10, 2)->default(0);
            $table->enum('video_quality', ['480p', '720p', '1080p', '4K'])->default('720p');
            
            // Content
            $table->longText('transcript')->nullable();
            $table->longText('notes')->nullable();
            $table->json('attachments')->nullable();
            
            // Lesson Settings
            $table->integer('order')->default(1);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_free_preview')->default(false);
            $table->boolean('is_downloadable')->default(false);
            
            // Lesson Type and Requirements
            $table->enum('lesson_type', ['video', 'quiz', 'assignment', 'reading'])->default('video');
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->json('assignment_requirements')->nullable();
            $table->json('learning_objectives')->nullable();
            
            // Difficulty and Study Time
            $table->enum('difficulty_level', ['very_easy', 'easy', 'medium', 'hard', 'very_hard'])->default('medium');
            $table->integer('estimated_study_time_minutes')->default(0);
            
            // Stats (auto-calculated)
            $table->integer('view_count')->default(0);
            $table->decimal('avg_rating', 3, 1)->default(0);
            $table->integer('total_comments')->default(0);
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['recorded_course_id', 'order']);
            $table->index(['course_section_id', 'order']);
            $table->index(['is_published', 'lesson_type']);
            $table->index(['is_free_preview', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
