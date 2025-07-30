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
        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('student_id');
            
            // Review Content
            $table->integer('rating')->default(5); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            
            // Review Metadata
            $table->boolean('is_helpful')->default(false);
            $table->integer('helpful_votes')->default(0);
            $table->boolean('is_verified_purchase')->default(false);
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('recorded_courses')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['student_id', 'course_id']);
            $table->index(['rating', 'status']);
            $table->index(['is_verified_purchase', 'status']);
            $table->index(['created_at', 'status']);
            
            // Unique constraint - one review per student per course
            $table->unique(['student_id', 'course_id'], 'unique_student_course_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_reviews');
    }
};
