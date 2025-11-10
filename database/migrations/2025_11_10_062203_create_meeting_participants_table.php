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
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Participation tracking
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->integer('duration_seconds')->default(0);

            // Role and permissions
            $table->boolean('is_host')->default(false);

            // Connection quality tracking
            $table->enum('connection_quality', ['excellent', 'good', 'fair', 'poor'])->default('good');

            $table->timestamps();

            // Indexes for performance
            $table->index(['meeting_id', 'user_id'], 'idx_meeting_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};
