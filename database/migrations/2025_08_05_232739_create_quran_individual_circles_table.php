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
        Schema::create('quran_individual_circles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained('quran_subscriptions')->onDelete('cascade');
            
            $table->string('circle_code', 50)->unique();
            $table->string('name')->nullable(); // e.g., "Individual Circle - Ahmed Ali"
            $table->text('description')->nullable();
            
            // Circle Configuration
            $table->enum('specialization', ['memorization', 'recitation', 'interpretation', 'arabic_language', 'complete'])->default('memorization');
            $table->enum('memorization_level', ['beginner', 'elementary', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->integer('total_sessions'); // From package
            $table->integer('sessions_scheduled')->default(0);
            $table->integer('sessions_completed')->default(0);
            $table->integer('sessions_remaining')->default(0);
            
            // Progress Tracking
            $table->integer('current_surah')->nullable();
            $table->integer('current_verse')->nullable();
            $table->integer('verses_memorized')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);
            
            // Session Configuration
            $table->integer('default_duration_minutes')->default(45);
            $table->json('preferred_times')->nullable(); // Teacher's preferred times
            
            // Status
            $table->enum('status', ['pending', 'active', 'completed', 'suspended', 'cancelled'])->default('pending');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('last_session_at')->nullable();
            
            // Meeting Configuration
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->boolean('recording_enabled')->default(false);
            
            // Learning Materials
            $table->json('materials_used')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->text('notes')->nullable();
            $table->text('teacher_notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['quran_teacher_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['subscription_id']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_individual_circles');
    }
};