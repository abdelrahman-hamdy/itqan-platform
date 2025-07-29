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
        Schema::create('recorded_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            
            // Course Information
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description');
            $table->text('description_en')->nullable();
            $table->string('course_code')->unique();
            $table->string('thumbnail_url')->nullable();
            $table->string('trailer_video_url')->nullable();
            
            // Course Details
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('duration_hours')->default(0);
            $table->enum('language', ['ar', 'en', 'both'])->default('ar');
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->boolean('is_free')->default(false);
            
            // Publishing
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('enrollment_deadline')->nullable();
            $table->boolean('completion_certificate')->default(true);
            
            // Course Content
            $table->json('prerequisites')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->json('course_materials')->nullable();
            
            // Stats (auto-calculated)
            $table->integer('total_sections')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->integer('total_duration_minutes')->default(0);
            $table->decimal('avg_rating', 3, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_enrollments')->default(0);
            
            // Course Settings
            $table->enum('difficulty_level', ['very_easy', 'easy', 'medium', 'hard', 'very_hard'])->default('medium');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            
            // SEO
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'is_published']);
            $table->index(['instructor_id', 'is_published']);
            $table->index(['subject_id', 'grade_level_id']);
            $table->index(['status', 'is_published']);
            $table->index(['category', 'level']);
            $table->index(['is_featured', 'is_published']);
            $table->index(['created_at', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recorded_courses');
    }
};
