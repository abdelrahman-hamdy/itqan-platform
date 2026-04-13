<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: Add supervisor user 216 (SUP-01-0007, profile 22) to the 5 supervised
 * conversations for teachers 90 and 136.
 *
 * Root cause: addSupervisorToTeacherGroups() adds to chat_group_members but not
 * wire_participants, so the supervisor can't see the conversations in WireChat.
 */
return new class extends Migration
{
    private const SUPERVISOR_USER_ID = 216;

    private const CONVERSATION_IDS = [175, 180, 200, 212, 281];

    private const MISSING_CHAT_GROUP_IDS = [142, 209];

    public function up(): void
    {
        // 1. Add user 216 to wire_participants for all 5 conversations (idempotent)
        foreach (self::CONVERSATION_IDS as $conversationId) {
            $exists = DB::table('wire_participants')
                ->where('conversation_id', $conversationId)
                ->where('participantable_id', self::SUPERVISOR_USER_ID)
                ->where('participantable_type', 'App\\Models\\User')
                ->exists();

            if (! $exists) {
                DB::table('wire_participants')->insert([
                    'conversation_id' => $conversationId,
                    'participantable_id' => self::SUPERVISOR_USER_ID,
                    'participantable_type' => 'App\\Models\\User',
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Add user 216 to chat_group_members for groups 142 and 209 (missing entirely)
        foreach (self::MISSING_CHAT_GROUP_IDS as $groupId) {
            $exists = DB::table('chat_group_members')
                ->where('group_id', $groupId)
                ->where('user_id', self::SUPERVISOR_USER_ID)
                ->exists();

            if (! $exists) {
                DB::table('chat_group_members')->insert([
                    'group_id' => $groupId,
                    'user_id' => self::SUPERVISOR_USER_ID,
                    'role' => 'moderator',
                    'can_send_messages' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove the added wire_participants entries
        DB::table('wire_participants')
            ->whereIn('conversation_id', self::CONVERSATION_IDS)
            ->where('participantable_id', self::SUPERVISOR_USER_ID)
            ->where('participantable_type', 'App\\Models\\User')
            ->delete();

        // Remove the added chat_group_members entries
        DB::table('chat_group_members')
            ->whereIn('group_id', self::MISSING_CHAT_GROUP_IDS)
            ->where('user_id', self::SUPERVISOR_USER_ID)
            ->delete();
    }
};
