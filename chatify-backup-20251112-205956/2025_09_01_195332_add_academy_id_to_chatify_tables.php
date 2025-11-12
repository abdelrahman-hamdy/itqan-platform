<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add academy_id to ch_messages table for multi-tenant isolation
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')->nullable()->after('to_id');
            $table->enum('message_type', ['text', 'file', 'voice', 'image'])->default('text')->after('body');
            $table->boolean('is_read')->default(false)->after('seen');
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->unsignedBigInteger('group_id')->nullable()->after('academy_id');
            
            // Add indexes for performance
            $table->index('academy_id');
            $table->index('group_id');
            $table->index(['academy_id', 'from_id']);
            $table->index(['academy_id', 'to_id']);
            
            // Add foreign key constraint
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
        });
        
        // Add academy_id to ch_favorites table
        Schema::table('ch_favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')->nullable()->after('favorite_id');
            
            // Add index
            $table->index(['academy_id', 'user_id']);
            
            // Add foreign key constraint
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
        });
        
        // Update existing messages with academy_id from users
        DB::statement('
            UPDATE ch_messages m
            INNER JOIN users u ON m.from_id = u.id
            SET m.academy_id = u.academy_id
            WHERE m.academy_id IS NULL
        ');
        
        // Update existing favorites with academy_id from users
        DB::statement('
            UPDATE ch_favorites f
            INNER JOIN users u ON f.user_id = u.id
            SET f.academy_id = u.academy_id
            WHERE f.academy_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropIndex(['academy_id']);
            $table->dropIndex(['group_id']);
            $table->dropIndex(['academy_id', 'from_id']);
            $table->dropIndex(['academy_id', 'to_id']);
            $table->dropColumn(['academy_id', 'message_type', 'is_read', 'read_at', 'group_id']);
        });
        
        Schema::table('ch_favorites', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropIndex(['academy_id', 'user_id']);
            $table->dropColumn('academy_id');
        });
    }
};
