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
        Schema::create('teaching_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('course_id')->nullable(); // Can be null for individual sessions
            $table->unsignedBigInteger('teacher_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['individual', 'group', 'assessment'])->default('group');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->datetime('scheduled_at');
            $table->datetime('started_at')->nullable();
            $table->datetime('ended_at')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->string('google_event_id')->nullable(); // Google Calendar event ID
            $table->text('google_meet_url')->nullable(); // Google Meet URL
            $table->text('notes')->nullable(); // Teacher notes
            $table->boolean('attendance_taken')->default(false);
            $table->timestamps();

            $table->index(['academy_id', 'status']);
            $table->index(['teacher_id', 'scheduled_at']);
            $table->index(['course_id', 'scheduled_at']);
            $table->index(['scheduled_at']);
            $table->index(['google_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_sessions');
    }
};
