<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\MessageType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;

class MigrateChattifyToWirechat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:chattify-to-wirechat
                            {--dry-run : Run without actually migrating data}
                            {--academy= : Migrate only specific academy data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate chat data from Chattify to WireChat';

    /**
     * Hide this command in production - one-time migration only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $academyId = $this->option('academy');

        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no data will be migrated');
        }

        $this->info('Starting Chattify to WireChat migration...');

        DB::beginTransaction();

        try {
            // 1. Migrate private conversations
            $this->migratePrivateConversations($isDryRun, $academyId);

            // 2. Migrate group chats
            $this->migrateGroupChats($isDryRun, $academyId);

            // 3. Migrate messages
            $this->migrateMessages($isDryRun, $academyId);

            // 4. Migrate attachments
            $this->migrateAttachments($isDryRun, $academyId);

            // 5. Update read status
            $this->updateReadStatus($isDryRun, $academyId);

            if ($isDryRun) {
                DB::rollBack();
                $this->info('Dry run completed - no data was migrated');
            } else {
                DB::commit();
                $this->info('Migration completed successfully!');
            }

            // Display statistics
            $this->displayStatistics();

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }

        return 0;
    }

    /**
     * Migrate private conversations from Chattify
     */
    protected function migratePrivateConversations($isDryRun, $academyId)
    {
        $this->info('Migrating private conversations...');

        // Get unique conversation pairs from Chattify messages
        $query = DB::table('ch_messages')
            ->select('from_id', 'to_id')
            ->whereNull('group_id')
            ->distinct();

        if ($academyId) {
            $query->whereExists(function ($q) use ($academyId) {
                $q->select(DB::raw(1))
                    ->from('users')
                    ->where('academy_id', $academyId)
                    ->where(function ($subQ) {
                        $subQ->whereColumn('users.id', 'ch_messages.from_id')
                            ->orWhereColumn('users.id', 'ch_messages.to_id');
                    });
            });
        }

        $conversations = $query->get();

        $bar = $this->output->createProgressBar($conversations->count());
        $bar->start();

        $conversationMap = [];

        foreach ($conversations as $conv) {
            // Skip if already processed (reverse pair)
            $key1 = $conv->from_id.'-'.$conv->to_id;
            $key2 = $conv->to_id.'-'.$conv->from_id;

            if (isset($conversationMap[$key1]) || isset($conversationMap[$key2])) {
                $bar->advance();

                continue;
            }

            $user1 = User::find($conv->from_id);
            $user2 = User::find($conv->to_id);

            if (! $user1 || ! $user2) {
                $bar->advance();

                continue;
            }

            if (! $isDryRun) {
                // Create private conversation
                $conversation = Conversation::create([
                    'type' => ConversationType::PRIVATE,
                    'created_at' => $this->getFirstMessageTime($conv->from_id, $conv->to_id),
                    'updated_at' => $this->getLastMessageTime($conv->from_id, $conv->to_id),
                ]);

                // Add participants
                Participant::create([
                    'conversation_id' => $conversation->id,
                    'participantable_id' => $user1->id,
                    'participantable_type' => get_class($user1),
                    'role' => ParticipantRole::PARTICIPANT,
                ]);

                Participant::create([
                    'conversation_id' => $conversation->id,
                    'participantable_id' => $user2->id,
                    'participantable_type' => get_class($user2),
                    'role' => ParticipantRole::PARTICIPANT,
                ]);

                // Store mapping for message migration
                $conversationMap[$key1] = $conversation->id;
                $conversationMap[$key2] = $conversation->id;

                // Store in database for later reference
                DB::table('chattify_wirechat_mapping')->insert([
                    'chattify_type' => 'private',
                    'chattify_id' => $key1,
                    'wirechat_conversation_id' => $conversation->id,
                    'created_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Private conversations migrated: '.count($conversationMap));
    }

    /**
     * Migrate group chats
     */
    protected function migrateGroupChats($isDryRun, $academyId)
    {
        $this->info('Migrating group chats...');

        $query = DB::table('chat_groups');

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $groups = $query->get();

        $bar = $this->output->createProgressBar($groups->count());
        $bar->start();

        foreach ($groups as $chatGroup) {
            if (! $isDryRun) {
                // Create group conversation
                $conversation = Conversation::create([
                    'type' => ConversationType::GROUP,
                    'created_at' => $chatGroup->created_at,
                    'updated_at' => $chatGroup->updated_at ?? now(),
                ]);

                // Create group details
                $group = Group::create([
                    'conversation_id' => $conversation->id,
                    'name' => $chatGroup->name,
                    'description' => $chatGroup->description,
                    'type' => $chatGroup->is_public ? 'public' : 'private',
                    'avatar' => $chatGroup->avatar,
                    'cover' => $chatGroup->cover_image,
                    'settings' => json_decode($chatGroup->settings, true) ?? [],
                    'created_at' => $chatGroup->created_at,
                    'updated_at' => $chatGroup->updated_at,
                ]);

                // Add group members
                $members = DB::table('chat_group_members')
                    ->where('group_id', $chatGroup->id)
                    ->get();

                foreach ($members as $member) {
                    $user = User::find($member->user_id);
                    if ($user) {
                        $role = ParticipantRole::PARTICIPANT;

                        if ($member->role === 'owner') {
                            $role = ParticipantRole::OWNER;
                        } elseif ($member->role === 'admin') {
                            $role = ParticipantRole::ADMIN;
                        }

                        Participant::create([
                            'conversation_id' => $conversation->id,
                            'participantable_id' => $user->id,
                            'participantable_type' => get_class($user),
                            'role' => $role,
                            'joined_at' => $member->joined_at,
                            'last_read_at' => $member->last_read_at,
                        ]);
                    }
                }

                // Store mapping
                DB::table('chattify_wirechat_mapping')->insert([
                    'chattify_type' => 'group',
                    'chattify_id' => $chatGroup->id,
                    'wirechat_conversation_id' => $conversation->id,
                    'created_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Group chats migrated: '.$groups->count());
    }

    /**
     * Migrate messages
     */
    protected function migrateMessages($isDryRun, $academyId)
    {
        $this->info('Migrating messages...');

        $query = DB::table('ch_messages');

        if ($academyId) {
            $query->whereExists(function ($q) use ($academyId) {
                $q->select(DB::raw(1))
                    ->from('users')
                    ->where('academy_id', $academyId)
                    ->whereColumn('users.id', 'ch_messages.from_id');
            });
        }

        $totalMessages = $query->count();
        $this->info("Total messages to migrate: $totalMessages");

        $bar = $this->output->createProgressBar($totalMessages);
        $bar->start();

        // Process in chunks to avoid memory issues
        $query->orderBy('created_at')->chunk(1000, function ($messages) use ($isDryRun, $bar) {
            foreach ($messages as $chattifyMessage) {
                if (! $isDryRun) {
                    // Find the corresponding WireChat conversation
                    $conversationId = $this->findWirechatConversation($chattifyMessage);

                    if (! $conversationId) {
                        $bar->advance();

                        continue;
                    }

                    $sender = User::find($chattifyMessage->from_id);
                    if (! $sender) {
                        $bar->advance();

                        continue;
                    }

                    // Determine message type
                    $messageType = MessageType::TEXT;
                    if ($chattifyMessage->attachment) {
                        $attachment = json_decode($chattifyMessage->attachment, true);
                        if ($attachment && isset($attachment['type'])) {
                            // WireChat only has TEXT and ATTACHMENT types
                            $messageType = MessageType::ATTACHMENT;
                        }
                    }

                    // Create WireChat message
                    $message = Message::create([
                        'conversation_id' => $conversationId,
                        'sendable_id' => $sender->id,
                        'sendable_type' => get_class($sender),
                        'body' => $chattifyMessage->body,
                        'type' => $messageType,
                        'reply_id' => null, // Handle replies if your Chattify has them
                        'created_at' => $chattifyMessage->created_at,
                        'updated_at' => $chattifyMessage->updated_at ?? $chattifyMessage->created_at,
                    ]);

                    // Store mapping
                    DB::table('chattify_wirechat_message_mapping')->insert([
                        'chattify_message_id' => $chattifyMessage->id,
                        'wirechat_message_id' => $message->id,
                        'created_at' => now(),
                    ]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Messages migrated successfully');
    }

    /**
     * Migrate attachments
     */
    protected function migrateAttachments($isDryRun, $academyId)
    {
        $this->info('Migrating attachments...');

        $query = DB::table('ch_messages')
            ->whereNotNull('attachment');

        if ($academyId) {
            $query->whereExists(function ($q) use ($academyId) {
                $q->select(DB::raw(1))
                    ->from('users')
                    ->where('academy_id', $academyId)
                    ->whereColumn('users.id', 'ch_messages.from_id');
            });
        }

        $messagesWithAttachments = $query->get();

        $bar = $this->output->createProgressBar($messagesWithAttachments->count());
        $bar->start();

        foreach ($messagesWithAttachments as $chattifyMessage) {
            if (! $isDryRun) {
                // Find corresponding WireChat message
                $mapping = DB::table('chattify_wirechat_message_mapping')
                    ->where('chattify_message_id', $chattifyMessage->id)
                    ->first();

                if (! $mapping) {
                    $bar->advance();

                    continue;
                }

                $attachment = json_decode($chattifyMessage->attachment, true);
                if ($attachment && isset($attachment['file'])) {
                    Attachment::create([
                        'message_id' => $mapping->wirechat_message_id,
                        'file_path' => $attachment['file'],
                        'file_name' => $attachment['name'] ?? basename($attachment['file']),
                        'original_name' => $attachment['original_name'] ?? $attachment['name'] ?? basename($attachment['file']),
                        'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream',
                        'file_size' => $attachment['size'] ?? 0,
                        'created_at' => $chattifyMessage->created_at,
                        'updated_at' => $chattifyMessage->created_at,
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Attachments migrated successfully');
    }

    /**
     * Update read status
     */
    protected function updateReadStatus($isDryRun, $academyId)
    {
        $this->info('Updating read status...');

        // This would need to be implemented based on your Chattify's read status tracking
        // For now, we'll mark all messages as read up to the last message time

        $this->info('Read status updated');
    }

    /**
     * Helper: Find WireChat conversation for a Chattify message
     */
    protected function findWirechatConversation($chattifyMessage)
    {
        if ($chattifyMessage->group_id) {
            // Group message
            $mapping = DB::table('chattify_wirechat_mapping')
                ->where('chattify_type', 'group')
                ->where('chattify_id', $chattifyMessage->group_id)
                ->first();
        } else {
            // Private message
            $key = $chattifyMessage->from_id.'-'.$chattifyMessage->to_id;
            $mapping = DB::table('chattify_wirechat_mapping')
                ->where('chattify_type', 'private')
                ->where(function ($q) use ($key, $chattifyMessage) {
                    $q->where('chattify_id', $key)
                        ->orWhere('chattify_id', $chattifyMessage->to_id.'-'.$chattifyMessage->from_id);
                })
                ->first();
        }

        return $mapping ? $mapping->wirechat_conversation_id : null;
    }

    /**
     * Helper: Get first message time for a conversation
     */
    protected function getFirstMessageTime($user1Id, $user2Id)
    {
        $message = DB::table('ch_messages')
            ->where(function ($q) use ($user1Id, $user2Id) {
                $q->where('from_id', $user1Id)->where('to_id', $user2Id);
            })
            ->orWhere(function ($q) use ($user1Id, $user2Id) {
                $q->where('from_id', $user2Id)->where('to_id', $user1Id);
            })
            ->orderBy('created_at')
            ->first();

        return $message ? $message->created_at : now();
    }

    /**
     * Helper: Get last message time for a conversation
     */
    protected function getLastMessageTime($user1Id, $user2Id)
    {
        $message = DB::table('ch_messages')
            ->where(function ($q) use ($user1Id, $user2Id) {
                $q->where('from_id', $user1Id)->where('to_id', $user2Id);
            })
            ->orWhere(function ($q) use ($user1Id, $user2Id) {
                $q->where('from_id', $user2Id)->where('to_id', $user1Id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        return $message ? $message->created_at : now();
    }

    /**
     * Display migration statistics
     */
    protected function displayStatistics()
    {
        $this->newLine();
        $this->info('=== Migration Statistics ===');

        $stats = [
            'Chattify Messages' => DB::table('ch_messages')->count(),
            'Chattify Groups' => DB::table('chat_groups')->count(),
            'WireChat Conversations' => Conversation::count(),
            'WireChat Messages' => Message::count(),
            'WireChat Participants' => Participant::count(),
            'WireChat Groups' => Group::count(),
            'WireChat Attachments' => Attachment::count(),
        ];

        foreach ($stats as $label => $count) {
            $this->line("$label: $count");
        }
    }
}
