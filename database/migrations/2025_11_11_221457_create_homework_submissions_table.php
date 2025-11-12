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
        // Only create table if it doesn't exist (might exist from earlier migration)
        if (!Schema::hasTable('homework_submissions')) {
            Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->morphs('submitable'); // Polymorphic to any session type
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('submission_code')->unique();
            $table->text('content')->nullable(); // Student's text answer
            $table->string('file_path')->nullable(); // Uploaded file
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->decimal('grade', 3, 1)->nullable(); // 0-10
            $table->text('teacher_feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users');
            $table->string('status'); // pending, submitted, graded, late
            $table->timestamps();

            // morphs() already creates index on submitable_type and submitable_id, so we don't need to add it again
            $table->index(['student_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
    }
};
