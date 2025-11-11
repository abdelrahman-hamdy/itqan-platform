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
        Schema::create('academic_individual_lessons', function (Blueprint $table) {
            $table->id();
            
            // Basic relationships (adapted from Quran structure)
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_teacher_id')->constrained('academic_teacher_profiles')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academic_subscription_id')->nullable()->constrained()->onDelete('cascade');
            
            // Academic-specific fields (replacing Quran fields)
            $table->string('lesson_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('academic_subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_grade_level_id')->constrained()->onDelete('cascade');
            
            // Session management (same as Quran)
            $table->integer('total_sessions')->default(0);
            $table->integer('sessions_scheduled')->default(0);
            $table->integer('sessions_completed')->default(0);
            $table->integer('sessions_remaining')->default(0);
            
            // Academic progress (replacing Quran progress)
            $table->text('lesson_topics_covered')->nullable();
            $table->text('current_topics')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            
            // Session settings (same as Quran)
            $table->integer('default_duration_minutes')->default(60);
            $table->json('preferred_times')->nullable();
            
            // Status and dates (same as Quran)
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_session_at')->nullable();
            
            // Meeting configuration (same as Quran)
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->boolean('recording_enabled')->default(false);
            
            // Additional fields (same as Quran)
            $table->json('materials_used')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->text('notes')->nullable();
            $table->text('teacher_notes')->nullable();
            
            // Meeting timing settings (same as Quran)
            $table->integer('preparation_minutes')->default(5);
            $table->integer('ending_buffer_minutes')->default(5);
            $table->integer('late_join_grace_period_minutes')->default(10);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['academic_teacher_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_individual_lessons');
    }
};
