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
        Schema::create('interactive_course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('interactive_courses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            // Progress metrics
            $table->integer('total_sessions')->default(0)
                ->comment('Total number of sessions in the course');
            $table->integer('sessions_attended')->default(0)
                ->comment('Number of sessions student attended');
            $table->integer('sessions_completed')->default(0)
                ->comment('Number of sessions completed');
            $table->decimal('attendance_percentage', 5, 2)->default(0.00)
                ->comment('Overall attendance percentage');

            // Homework metrics
            $table->integer('homework_assigned')->default(0)
                ->comment('Total homework assignments');
            $table->integer('homework_submitted')->default(0)
                ->comment('Homework submitted');
            $table->integer('homework_graded')->default(0)
                ->comment('Homework graded');
            $table->decimal('average_homework_score', 5, 2)->nullable()
                ->comment('Average homework score');

            // Overall performance
            $table->decimal('overall_score', 5, 2)->nullable()
                ->comment('Overall course score/grade');
            $table->enum('progress_status', ['not_started', 'in_progress', 'completed', 'dropped'])
                ->default('not_started')
                ->comment('Student progress status');

            // Completion tracking
            $table->decimal('completion_percentage', 5, 2)->default(0.00)
                ->comment('Overall course completion percentage');
            $table->timestamp('started_at')->nullable()
                ->comment('When student started the course');
            $table->timestamp('completed_at')->nullable()
                ->comment('When student completed the course');
            $table->timestamp('last_activity_at')->nullable()
                ->comment('Last time student had activity');

            // Additional metrics
            $table->integer('days_since_last_activity')->default(0)
                ->comment('Days since last activity (for dropout detection)');
            $table->boolean('is_at_risk')->default(false)
                ->comment('Student at risk of dropping out');

            $table->timestamps();

            // Indexes for performance
            $table->unique(['course_id', 'student_id']);
            $table->index(['academy_id', 'progress_status']);
            $table->index('last_activity_at');
            $table->index('is_at_risk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactive_course_progress');
    }
};
