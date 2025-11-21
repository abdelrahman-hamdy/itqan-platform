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
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recorded_course_id')->constrained('recorded_courses')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('course_sections')->nullOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->integer('passing_score')->default(70); // Percentage
            $table->integer('max_attempts')->default(3);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('order')->default(0);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_quizzes');
    }
};
