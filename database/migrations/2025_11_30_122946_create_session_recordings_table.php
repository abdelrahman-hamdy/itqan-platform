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
        Schema::create('session_recordings', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationships (recordable: session that was recorded)
            $table->morphs('recordable'); // Creates recordable_type and recordable_id

            // Recording details
            $table->string('recording_id')->unique(); // LiveKit egress ID
            $table->string('meeting_room'); // LiveKit room name
            $table->enum('status', ['recording', 'processing', 'completed', 'failed', 'deleted'])->default('recording');

            // Timing
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds

            // File information
            $table->string('file_path')->nullable(); // Path on LiveKit server
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('file_format')->default('mp4');
            $table->json('metadata')->nullable(); // Additional recording metadata

            // Processing information
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable(); // When recording became available

            $table->timestamps();

            // Indexes
            $table->index(['recordable_type', 'recordable_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('recording_id');
            $table->index('meeting_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_recordings');
    }
};
