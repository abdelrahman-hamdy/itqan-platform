<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recorded_course_id');
            $table->unsignedBigInteger('course_section_id')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            
            $table->enum('quiz_type', ['lesson', 'section', 'course', 'assignment'])->default('lesson');
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('max_attempts')->default(3);
            $table->decimal('pass_score_percentage', 5, 2)->default(70);
            
            $table->integer('questions_count')->default(0);
            $table->integer('total_points')->default(0);
            $table->boolean('is_randomized')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->boolean('allow_review')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_published')->default(false);
            
            $table->enum('difficulty_level', ['very_easy', 'easy', 'medium', 'hard', 'very_hard'])->default('medium');
            $table->text('instructions')->nullable();
            $table->text('completion_message')->nullable();
            $table->text('failure_message')->nullable();
            
            // Stats
            $table->integer('attempts_count')->default(0);
            $table->decimal('avg_score', 5, 2)->default(0);
            $table->decimal('pass_rate_percentage', 5, 2)->default(0);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['recorded_course_id', 'is_published']);
            $table->index(['course_section_id', 'is_published']);
            $table->index(['lesson_id']);
            $table->index(['quiz_type', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_quizzes');
    }
};
