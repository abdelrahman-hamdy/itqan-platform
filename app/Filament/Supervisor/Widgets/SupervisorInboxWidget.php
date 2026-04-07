<?php

namespace App\Filament\Supervisor\Widgets;

use App\Models\ChatGroup;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;

class SupervisorInboxWidget extends Widget
{
    protected static bool $isDiscoverable = false;

    protected string $view = 'filament.supervisor.widgets.inbox-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 1;

    /**
     * Cached supervised conversation IDs (request-scoped).
     */
    protected ?array $cachedConversationIds = null;

    /**
     * Get count of unread messages only from supervised conversations.
     */
    public function getUnreadCount(): int
    {
        $user = Auth::user();
        $supervisedConversationIds = $this->getSupervisedConversationIds();

        if (empty($supervisedConversationIds)) {
            return 0;
        }

        // Get participant read-at timestamps in one query
        $participants = Participant::query()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', User::class)
            ->whereIn('conversation_id', $supervisedConversationIds)
            ->get();

        $readAtMap = $participants->pluck('conversation_read_at', 'conversation_id');
        $participantConversationIds = $readAtMap->keys()->toArray();

        if (empty($participantConversationIds)) {
            return 0;
        }

        // Get user's own participant IDs to exclude own messages
        $ownParticipantIds = $participants->pluck('id')->toArray();

        // Single query to count all unread messages across conversations
        return Message::whereIn('conversation_id', $participantConversationIds)
            ->whereNull('deleted_at')
            ->whereNotIn('participant_id', $ownParticipantIds)
            ->where(function ($q) use ($readAtMap) {
                foreach ($readAtMap as $convId => $readAt) {
                    if ($readAt) {
                        $q->orWhere(function ($sub) use ($convId, $readAt) {
                            $sub->where('conversation_id', $convId)
                                ->where('created_at', '>', $readAt);
                        });
                    } else {
                        $q->orWhere('conversation_id', $convId);
                    }
                }
            })
            ->count();
    }

    /**
     * Get recent conversations where supervisor is assigned.
     */
    public function getRecentConversations(): array
    {
        $user = Auth::user();
        $userId = $user->id;
        $userType = User::class;

        $supervisedConversationIds = $this->getSupervisedConversationIds();

        if (empty($supervisedConversationIds)) {
            return [];
        }

        $participantEntries = Participant::query()
            ->where('participantable_id', $userId)
            ->where('participantable_type', $userType)
            ->whereIn('conversation_id', $supervisedConversationIds)
            ->with(['conversation' => function ($q) {
                $q->with(['lastMessage', 'participants.participantable']);
            }])
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->filter(fn ($p) => $p->conversation !== null);

        $ownParticipantIds = $participantEntries->pluck('id')->toArray();
        $conversationIds = $participantEntries->pluck('conversation_id')->toArray();

        // Load chat groups only for displayed conversations (not all supervised ones)
        $chatGroupsByConversation = ChatGroup::whereIn('conversation_id', $conversationIds)
            ->get()
            ->keyBy('conversation_id');

        // Batch unread check: find which conversations have newer messages
        $readAtMap = $participantEntries->pluck('conversation_read_at', 'conversation_id');
        $conversationsWithUnread = collect();

        if (! empty($conversationIds)) {
            $conversationsWithUnread = Message::whereIn('conversation_id', $conversationIds)
                ->whereNull('deleted_at')
                ->whereNotIn('participant_id', $ownParticipantIds)
                ->where(function ($q) use ($readAtMap) {
                    foreach ($readAtMap as $convId => $readAt) {
                        if ($readAt) {
                            $q->orWhere(function ($sub) use ($convId, $readAt) {
                                $sub->where('conversation_id', $convId)
                                    ->where('created_at', '>', $readAt);
                            });
                        } else {
                            $q->orWhere('conversation_id', $convId);
                        }
                    }
                })
                ->distinct()
                ->pluck('conversation_id');
        }

        return $participantEntries
            ->map(function ($participant) use ($userId, $chatGroupsByConversation, $conversationsWithUnread) {
                $conversation = $participant->conversation;
                $otherParticipant = $conversation->participants
                    ->where('participantable_id', '!=', $userId)
                    ->first();

                $chatGroup = $chatGroupsByConversation->get($conversation->id);

                return [
                    'id' => $conversation->id,
                    'name' => $chatGroup?->name
                        ?? ($conversation->isGroup()
                            ? $conversation->group?->name
                            : $otherParticipant?->participantable?->name),
                    'lastMessage' => $conversation->lastMessage?->body,
                    'unread' => $conversationsWithUnread->contains($conversation->id),
                    'time' => $conversation->lastMessage?->created_at?->diffForHumans(),
                    'type' => $chatGroup?->type,
                ];
            })
            ->toArray();
    }

    /**
     * Get conversation IDs for chats where this user is the assigned supervisor.
     * Cached per request to avoid duplicate queries.
     */
    protected function getSupervisedConversationIds(): array
    {
        if ($this->cachedConversationIds !== null) {
            return $this->cachedConversationIds;
        }

        $user = Auth::user();

        return $this->cachedConversationIds = ChatGroup::query()
            ->where('supervisor_id', $user->id)
            ->whereNotNull('conversation_id')
            ->notArchived()
            ->pluck('conversation_id')
            ->toArray();
    }

    public function getChatUrl(): string
    {
        $academy = Auth::user()->academy;
        $subdomain = $academy?->subdomain ?? 'default';

        return route('chats', ['subdomain' => $subdomain]);
    }
}
