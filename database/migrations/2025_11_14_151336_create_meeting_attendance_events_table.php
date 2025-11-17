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
        Schema::create('meeting_attendance_events', function (Blueprint $table) {
            $table->id();

            // Event identification (from LiveKit webhook)
            $table->string('event_id')->unique()->comment('LiveKit webhook event UUID - for idempotency');
            $table->enum('event_type', ['join', 'leave', 'reconnect', 'aborted'])->default('join');
            $table->timestamp('event_timestamp')->comment('From LiveKit webhook - exact join/leave time');

            // Session and user context
            $table->unsignedBigInteger('session_id')->comment('Polymorphic session ID');
            $table->string('session_type')->comment('QuranSession or AcademicSession class name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('academy_id')->nullable()->constrained()->onDelete('cascade');

            // LiveKit participant tracking
            $table->string('participant_sid')->comment('LiveKit participant session ID - unique per connection');
            $table->string('participant_identity')->comment('User identity sent to LiveKit');
            $table->string('participant_name')->nullable();

            // Duration tracking
            $table->timestamp('left_at')->nullable()->comment('Populated by participant_left event');
            $table->integer('duration_minutes')->nullable()->comment('Calculated: left_at - event_timestamp');
            $table->string('leave_event_id')->nullable()->comment('Event ID that closed this cycle');

            // Metadata and debugging
            $table->json('raw_webhook_data')->nullable()->comment('Full webhook payload for debugging');
            $table->string('termination_reason')->nullable()->comment('normal, aborted, timeout, etc.');

            $table->timestamps();

            // Indexes for performance
            $table->index(['session_id', 'session_type', 'user_id'], 'session_user_idx');
            $table->index(['session_id', 'event_timestamp'], 'session_time_idx');
            $table->index(['participant_sid', 'event_timestamp'], 'participant_time_idx');
            $table->index(['user_id', 'event_timestamp'], 'user_time_idx');
            $table->index('event_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendance_events');
    }
};
