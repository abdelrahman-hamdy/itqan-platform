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
        // Mapping table for conversations
        Schema::create('chattify_wirechat_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('chattify_type'); // 'private' or 'group'
            $table->string('chattify_id'); // Group ID or user pair key
            $table->unsignedBigInteger('wirechat_conversation_id');
            $table->timestamps();

            $table->index('chattify_type');
            $table->index('chattify_id');
            $table->index('wirechat_conversation_id');
        });

        // Mapping table for messages
        Schema::create('chattify_wirechat_message_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('chattify_message_id');
            $table->unsignedBigInteger('wirechat_message_id');
            $table->timestamps();

            $table->index('chattify_message_id');
            $table->index('wirechat_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chattify_wirechat_message_mapping');
        Schema::dropIfExists('chattify_wirechat_mapping');
    }
};