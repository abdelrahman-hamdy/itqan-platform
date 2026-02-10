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
        if (! Schema::hasTable('message_reactions')) {
            Schema::create('message_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('wire_messages')->cascadeOnDelete();
                $table->morphs('reacted_by'); // User who reacted
                $table->string('emoji', 10); // 👍, ❤️, 😂, 😮, 😢, 🙏, etc.
                $table->timestamps();

                // Prevent duplicate reactions (same user, same emoji, same message)
                $table->unique(['message_id', 'reacted_by_id', 'reacted_by_type', 'emoji'], 'unique_message_user_emoji');

                // Index for fast lookups
                $table->index(['message_id', 'emoji']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
