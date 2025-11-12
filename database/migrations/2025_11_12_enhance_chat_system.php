<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add delivered_at column to ch_messages if it doesn't exist
        if (!Schema::hasColumn('ch_messages', 'delivered_at')) {
            Schema::table('ch_messages', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->after('seen');
                $table->index(['from_id', 'to_id', 'delivered_at'], 'idx_messages_delivered');
            });
        }

        // Add chat_settings column to users table for notification preferences
        if (!Schema::hasColumn('users', 'chat_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('chat_settings')->nullable()->comment('Chat notification and preference settings');
            });
        }

        // Add last_typing_at column to track typing status
        if (!Schema::hasColumn('users', 'last_typing_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_typing_at')->nullable()->comment('Last time user was typing');
            });
        }

        // Add last_seen_at column for better presence tracking
        if (!Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->comment('Last time user was seen online');
                $table->index('last_seen_at');
            });
        }

        // Create message_reactions table for future emoji reactions
        if (!Schema::hasTable('message_reactions')) {
            Schema::create('message_reactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('message_id'); // UUID to match ch_messages
                $table->foreign('message_id')->references('id')->on('ch_messages')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('reaction', 50); // emoji or reaction type
                $table->timestamps();

                $table->unique(['message_id', 'user_id', 'reaction']);
                $table->index(['message_id', 'reaction']);
            });
        }

        // Create chat_message_edits table to track message edit history
        if (!Schema::hasTable('chat_message_edits')) {
            Schema::create('chat_message_edits', function (Blueprint $table) {
                $table->id();
                $table->uuid('message_id'); // UUID to match ch_messages
                $table->foreign('message_id')->references('id')->on('ch_messages')->onDelete('cascade');
                $table->foreignId('edited_by')->constrained('users')->onDelete('cascade');
                $table->text('original_body');
                $table->text('edited_body');
                $table->timestamp('edited_at');
                $table->timestamps();

                $table->index(['message_id', 'edited_at']);
            });
        }

        // Add reply_to column for message threading
        if (!Schema::hasColumn('ch_messages', 'reply_to')) {
            Schema::table('ch_messages', function (Blueprint $table) {
                $table->uuid('reply_to')->nullable();
                $table->foreign('reply_to')->references('id')->on('ch_messages')->nullOnDelete();
                $table->index('reply_to');
            });
        }

        // Add is_edited column to track if message was edited
        if (!Schema::hasColumn('ch_messages', 'is_edited')) {
            Schema::table('ch_messages', function (Blueprint $table) {
                $table->boolean('is_edited')->default(false)->after('seen');
                $table->timestamp('edited_at')->nullable()->after('is_edited');
            });
        }

        // Add is_pinned for important messages
        if (!Schema::hasColumn('ch_messages', 'is_pinned')) {
            Schema::table('ch_messages', function (Blueprint $table) {
                $table->boolean('is_pinned')->default(false);
                $table->timestamp('pinned_at')->nullable();
                $table->unsignedBigInteger('pinned_by')->nullable();
                // Only add group index if group_id column exists
                if (Schema::hasColumn('ch_messages', 'group_id')) {
                    $table->index(['group_id', 'is_pinned', 'pinned_at']);
                } else {
                    $table->index(['is_pinned', 'pinned_at']);
                }
            });
        }

        // Create push_subscriptions table for web push notifications
        if (!Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('endpoint', 500);
                $table->string('public_key')->nullable();
                $table->string('auth_token')->nullable();
                $table->string('content_encoding')->nullable();
                $table->json('device_info')->nullable(); // browser, OS, etc.
                $table->timestamps();

                $table->unique(['user_id', 'endpoint']);
                $table->index('user_id');
            });
        }

        // Create chat_blocked_users table for blocking functionality
        if (!Schema::hasTable('chat_blocked_users')) {
            Schema::create('chat_blocked_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('blocked_user_id')->constrained('users')->onDelete('cascade');
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'blocked_user_id']);
                $table->index('user_id');
                $table->index('blocked_user_id');
            });
        }

        // Add voice_duration for voice messages
        if (!Schema::hasColumn('ch_messages', 'voice_duration')) {
            Schema::table('ch_messages', function (Blueprint $table) {
                $table->integer('voice_duration')->nullable()->comment('Duration in seconds for voice messages');
            });
        }

        // Add forwarded_from to track forwarded messages
        if (!Schema::hasColumn('ch_messages', 'forwarded_from')) {
            Schema::table('ch_messages', function (Blueprint $table) {
                $table->uuid('forwarded_from')->nullable();
                $table->foreign('forwarded_from')->references('id')->on('ch_messages')->nullOnDelete();
            });
        }

        // Create typing_indicators table for better typing status tracking
        if (!Schema::hasTable('typing_indicators')) {
            Schema::create('typing_indicators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedBigInteger('conversation_id')->nullable();
                $table->unsignedBigInteger('group_id')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('expires_at');

                $table->index(['user_id', 'conversation_id']);
                $table->index(['user_id', 'group_id']);
                $table->index('expires_at');
            });
        }

        // Add indexes for better performance
        Schema::table('ch_messages', function (Blueprint $table) {
            $columns = Schema::getColumnListing('ch_messages');

            if (!in_array('idx_messages_conversation', DB::select("SHOW INDEX FROM ch_messages WHERE Key_name = 'idx_messages_conversation'"))) {
                $table->index(['from_id', 'to_id', 'created_at'], 'idx_messages_conversation');
            }

            if (!in_array('idx_messages_unread', DB::select("SHOW INDEX FROM ch_messages WHERE Key_name = 'idx_messages_unread'"))) {
                $table->index(['to_id', 'seen', 'created_at'], 'idx_messages_unread');
            }

            // Only add group index if group_id column exists
            if (in_array('group_id', $columns) && !in_array('idx_messages_group', DB::select("SHOW INDEX FROM ch_messages WHERE Key_name = 'idx_messages_group'"))) {
                $table->index(['group_id', 'created_at'], 'idx_messages_group');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove indexes first
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conversation');
            $table->dropIndex('idx_messages_unread');
            $table->dropIndex('idx_messages_group');
            $table->dropIndex('idx_messages_delivered');
        });

        // Drop new columns from ch_messages
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to']);
            $table->dropColumn('reply_to');
            $table->dropForeign(['forwarded_from']);
            $table->dropColumn('forwarded_from');
            $table->dropColumn(['delivered_at', 'is_edited', 'edited_at', 'is_pinned', 'pinned_at', 'pinned_by', 'voice_duration']);
        });

        // Drop new columns from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['chat_settings', 'last_typing_at', 'last_seen_at']);
        });

        // Drop new tables
        Schema::dropIfExists('typing_indicators');
        Schema::dropIfExists('chat_blocked_users');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('chat_message_edits');
        Schema::dropIfExists('message_reactions');
    }
};