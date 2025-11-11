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
        Schema::create('academic_homework', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('academic_subscription_id')->nullable()->constrained('academic_subscriptions')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');

            // Homework Assignment Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('requirements')->nullable();

            // File Attachments
            $table->json('teacher_files')->nullable(); // Files provided by teacher
            $table->text('reference_links')->nullable();

            // Submission Settings
            $table->enum('submission_type', ['text', 'file', 'both'])->default('both');
            $table->boolean('allow_late_submissions')->default(true);
            $table->integer('max_files')->default(5);
            $table->integer('max_file_size_mb')->default(10);
            $table->json('allowed_file_types')->nullable();

            // Deadlines
            $table->dateTime('assigned_at');
            $table->dateTime('due_date')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();

            // Grading Settings
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->enum('grading_scale', ['points', 'percentage', 'letter', 'pass_fail'])->default('points');
            $table->json('grading_criteria')->nullable();
            $table->boolean('auto_grade')->default(false);

            // Status
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('published');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(true);

            // Priority
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();

            // Statistics (cached)
            $table->integer('total_students')->default(0);
            $table->integer('submitted_count')->default(0);
            $table->integer('graded_count')->default(0);
            $table->integer('late_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['academic_session_id']);
            $table->index(['teacher_id', 'status']);
            $table->index(['due_date']);
            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_homework');
    }
};
