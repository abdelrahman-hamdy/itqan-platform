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
        Schema::create('quran_circle_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('circle_id')->constrained('quran_circles')->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->constrained('users')->onDelete('cascade');
            
            // Schedule Configuration
            $table->json('weekly_schedule'); // [{"day": "sunday", "time": "16:00", "duration": 60}, ...]
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->integer('default_duration_minutes')->default(60);
            
            // Schedule Status
            $table->boolean('is_active')->default(false);
            $table->datetime('schedule_starts_at'); // When to start generating sessions
            $table->datetime('schedule_ends_at')->nullable(); // Optional end date
            $table->datetime('last_generated_at')->nullable(); // Last time sessions were generated
            
            // Generation Configuration
            $table->integer('generate_ahead_days')->default(30); // Generate sessions X days ahead
            $table->integer('generate_before_hours')->default(1); // Create actual session X hours before
            
            // Template Settings for Generated Sessions
            $table->string('session_title_template')->nullable(); // e.g., "حلقة {circle_name} - {date}"
            $table->text('session_description_template')->nullable();
            $table->json('default_lesson_objectives')->nullable();
            
            // Meeting Configuration
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->boolean('recording_enabled')->default(false);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['circle_id', 'is_active']);
            $table->index(['quran_teacher_id', 'is_active']);
            $table->index(['academy_id', 'is_active']);
            $table->index(['is_active', 'last_generated_at']);
            $table->index(['schedule_starts_at', 'schedule_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_circle_schedules');
    }
};