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
        Schema::create('quran_homework_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_homework_id')->constrained('quran_session_homeworks')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');

            // New Memorization Progress
            $table->decimal('new_memorization_completed_pages', 4, 2)->default(0);
            $table->enum('new_memorization_quality', ['excellent', 'good', 'needs_improvement', 'not_completed'])->nullable();
            $table->text('new_memorization_teacher_notes')->nullable();

            // Review Progress
            $table->decimal('review_completed_pages', 4, 2)->default(0);
            $table->enum('review_quality', ['excellent', 'good', 'needs_improvement', 'not_completed'])->nullable();
            $table->text('review_teacher_notes')->nullable();

            // Overall Assessment
            $table->decimal('overall_score', 3, 1)->nullable(); // 0.0 to 10.0
            $table->enum('completion_status', ['not_started', 'in_progress', 'completed', 'partially_completed'])->default('not_started');
            $table->boolean('submitted_by_student')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_by_teacher_at')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Unique constraint - one assignment per student per session homework
            $table->unique(['session_homework_id', 'student_id'], 'unique_homework_assignment');
            $table->index(['student_id', 'completion_status']);
            $table->index(['session_id', 'student_id']);
            $table->index('evaluated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_homework_assignments');
    }
};
