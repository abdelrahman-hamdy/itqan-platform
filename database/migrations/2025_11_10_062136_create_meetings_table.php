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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to sessions
            $table->string('meetable_type');
            $table->unsignedBigInteger('meetable_id');

            // Academy association
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');

            // LiveKit integration
            $table->string('livekit_room_name')->unique();
            $table->string('livekit_room_id')->nullable();

            // Meeting status
            $table->enum('status', ['scheduled', 'active', 'ended', 'cancelled'])->default('scheduled');

            // Timing
            $table->timestamp('scheduled_start_at');
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();

            // Recording
            $table->boolean('recording_enabled')->default(false);
            $table->string('recording_url', 500)->nullable();

            // Participants
            $table->integer('participant_count')->default(0);

            // Metadata (for platform-specific config)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['meetable_type', 'meetable_id'], 'idx_meetable');
            $table->index(['academy_id', 'status'], 'idx_academy_status');
            $table->index('scheduled_start_at', 'idx_scheduled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
