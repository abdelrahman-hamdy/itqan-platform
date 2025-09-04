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
        Schema::create('quran_session_homework', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Homework type flags
            $table->boolean('has_new_memorization')->default(false);
            $table->boolean('has_review')->default(false);
            $table->boolean('has_comprehensive_review')->default(false);

            // New memorization details
            $table->decimal('new_memorization_pages', 5, 2)->nullable();
            $table->string('new_memorization_surah')->nullable();
            $table->integer('new_memorization_from_verse')->nullable();
            $table->integer('new_memorization_to_verse')->nullable();

            // Review details
            $table->decimal('review_pages', 5, 2)->nullable();
            $table->string('review_surah')->nullable();
            $table->integer('review_from_verse')->nullable();
            $table->integer('review_to_verse')->nullable();

            // Comprehensive review details
            $table->json('comprehensive_review_surahs')->nullable();

            // Additional instructions and metadata
            $table->text('additional_instructions')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['session_id', 'is_active']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_session_homework');
    }
};
