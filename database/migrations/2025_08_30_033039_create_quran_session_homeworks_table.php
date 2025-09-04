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
        Schema::create('quran_session_homeworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Teacher who created

            // New Memorization (حفظ جديد)
            $table->decimal('new_memorization_pages', 4, 2)->default(0); // e.g., 1.5 pages
            $table->string('new_memorization_surah')->nullable();
            $table->integer('new_memorization_from_verse')->nullable();
            $table->integer('new_memorization_to_verse')->nullable();
            $table->text('new_memorization_notes')->nullable();

            // Review (مراجعة)
            $table->decimal('review_pages', 4, 2)->default(0); // e.g., 2.0 pages
            $table->string('review_surah')->nullable();
            $table->integer('review_from_verse')->nullable();
            $table->integer('review_to_verse')->nullable();
            $table->text('review_notes')->nullable();

            // General homework settings
            $table->text('additional_instructions')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['session_id', 'is_active']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_session_homeworks');
    }
};
