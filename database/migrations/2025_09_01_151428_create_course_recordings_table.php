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
        Schema::create('course_recordings', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('session_id')->constrained('interactive_course_sessions')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('interactive_courses')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('academic_teacher_profiles')->onDelete('cascade');
            
            // Recording details
            $table->string('recording_id')->unique();
            $table->string('meeting_room');
            $table->enum('status', ['recording', 'processing', 'completed', 'failed', 'deleted'])->default('recording');
            
            // Timing
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            
            // File information
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('file_format')->default('mp4');
            $table->json('metadata')->nullable(); // Additional recording metadata
            
            // Processing information
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['session_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['course_id', 'created_at']);
            $table->index('recording_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_recordings');
    }
};
