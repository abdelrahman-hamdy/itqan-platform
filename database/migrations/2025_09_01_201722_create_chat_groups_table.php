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
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['quran_circle', 'individual_session', 'academic_session', 'interactive_course', 'recorded_course', 'academy_announcement']);
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('avatar')->nullable();
            $table->json('settings')->nullable(); // For group-specific settings
            $table->boolean('is_active')->default(true);
            $table->integer('max_members')->nullable();
            
            // Links to the actual entities
            $table->foreignId('quran_circle_id')->nullable()->constrained('quran_circles')->onDelete('cascade');
            $table->foreignId('quran_session_id')->nullable()->constrained('quran_sessions')->onDelete('cascade');
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('interactive_course_id')->nullable()->constrained('interactive_courses')->onDelete('cascade');
            $table->foreignId('recorded_course_id')->nullable()->constrained('recorded_courses')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['academy_id', 'type']);
            $table->index('owner_id');
            $table->index('is_active');
        });
        
        // Create group members table
        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('chat_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['admin', 'moderator', 'member', 'observer'])->default('member');
            $table->boolean('can_send_messages')->default(true);
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_read_at')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamps();
            
            // Ensure unique membership
            $table->unique(['group_id', 'user_id']);
            
            // Indexes
            $table->index(['user_id', 'group_id']);
            $table->index('role');
        });
        
        // Update ch_messages table to support group messages
        Schema::table('ch_messages', function (Blueprint $table) {
            // Add group_id if it doesn't exist
            if (!Schema::hasColumn('ch_messages', 'group_id')) {
                $table->foreignId('group_id')->nullable()->after('to_id')->constrained('chat_groups')->onDelete('cascade');
                $table->index('group_id');
            }
            
            // Make to_id nullable for group messages
            $table->unsignedBigInteger('to_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove group_id from messages
        Schema::table('ch_messages', function (Blueprint $table) {
            if (Schema::hasColumn('ch_messages', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
        });
        
        Schema::dropIfExists('chat_group_members');
        Schema::dropIfExists('chat_groups');
    }
};
