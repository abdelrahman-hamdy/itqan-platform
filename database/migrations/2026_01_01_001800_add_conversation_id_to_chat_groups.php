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
        Schema::table('chat_groups', function (Blueprint $table) {
            // Link to WireChat conversation
            $table->unsignedBigInteger('conversation_id')->nullable()->after('supervisor_id');
            $table->index('conversation_id');

            // Add foreign key if the wire_conversations table exists
            if (Schema::hasTable('wire_conversations')) {
                $table->foreign('conversation_id')
                    ->references('id')
                    ->on('wire_conversations')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
