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
        Schema::create('interactive_course_homework', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('interactive_course_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            // Submission details
            $table->enum('submission_status', ['not_submitted', 'submitted', 'late', 'graded', 'returned'])
                ->default('not_submitted')
                ->comment('Status of homework submission');
            $table->text('submission_text')->nullable()
                ->comment('Text answer/response from student');
            $table->json('submission_files')->nullable()
                ->comment('Array of file paths uploaded by student');
            $table->timestamp('submitted_at')->nullable()
                ->comment('When student submitted the homework');
            $table->boolean('is_late')->default(false)
                ->comment('Whether submission was late');

            // Grading details
            $table->decimal('score', 5, 2)->nullable()
                ->comment('Score given by teacher');
            $table->text('teacher_feedback')->nullable()
                ->comment('Feedback from teacher');
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Teacher who graded this homework');
            $table->timestamp('graded_at')->nullable()
                ->comment('When homework was graded');

            // Additional metadata
            $table->integer('revision_count')->default(0)
                ->comment('Number of times student revised/resubmitted');
            $table->json('revision_history')->nullable()
                ->comment('History of all submissions/revisions');

            $table->timestamps();

            // Indexes for performance
            $table->index(['session_id', 'student_id']);
            $table->index(['academy_id', 'submission_status']);
            $table->index('submitted_at');
            $table->index('graded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactive_course_homework');
    }
};
