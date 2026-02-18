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
     * Get count of unread messages only from supervised conversations.
     */
    public function getUnreadCount(): int
    {
        $user = Auth::user();
        $supervisedConversationIds = $this->getSupervisedConversationIds();

        if (empty($supervisedConversationIds)) {
            return 0;
        }

        // Count unread messages in supervised conversations only
        $participant = Participant::query()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', User::class)
            ->whereIn('conversation_id', $supervisedConversationIds)
            ->get();

        $unreadCount = 0;
        foreach ($participant as $p) {
            if ($p->conversation_read_at) {
                $unreadCount += Message::where('conversation_id', $p->conversation_id)
                    ->whereNull('deleted_at')
                    ->where('created_at', '>', $p->conversation_read_at)
                    ->whereHas('participant', function ($q) use ($user) {
                        $q->where('participantable_id', '!=', $user->id)
                            ->orWhere('participantable_type', '!=', User::class);
                    })
                    ->count();
            } else {
                $unreadCount += Message::where('conversation_id', $p->conversation_id)
                    ->whereNull('deleted_at')
                    ->whereHas('participant', function ($q) use ($user) {
                        $q->where('participantable_id', '!=', $user->id)
                            ->orWhere('participantable_type', '!=', User::class);
                    })
                    ->count();
            }
        }

        return $unreadCount;
    }

    /**
     * Get recent conversations where supervisor is assigned.
     */
    public function getRecentConversations(): array
    {
        $user = Auth::user();
        $userId = $user->id;
        $userType = User::class;

        // Get conversation IDs from chat groups where this user is the supervisor
        $supervisedConversationIds = $this->getSupervisedConversationIds();

        if (empty($supervisedConversationIds)) {
            return [];
        }

        return Participant::query()
            ->where('participantable_id', $userId)
            ->where('participantable_type', $userType)
            ->whereIn('conversation_id', $supervisedConversationIds)
            ->with(['conversation' => function ($q) {
                $q->with(['lastMessage', 'participants.participantable']);
            }])
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->filter(fn ($p) => $p->conversation !== null)
            ->map(function ($participant) use ($userId, $userType) {
                $conversation = $participant->conversation;
                $otherParticipant = $conversation->participants
                    ->where('participantable_id', '!=', $userId)
                    ->first();

                // Check for unread messages (messages after conversation_read_at, not sent by user)
                $hasUnread = false;
                if ($participant->conversation_read_at) {
                    $hasUnread = Message::where('conversation_id', $conversation->id)
                        ->whereNull('deleted_at')
                        ->where('created_at', '>', $participant->conversation_read_at)
                        ->whereHas('participant', function ($q) use ($userId, $userType) {
                            $q->where('participantable_id', '!=', $userId)
                                ->orWhere('participantable_type', '!=', $userType);
                        })
                        ->exists();
                } else {
                    // Never read - check if any messages from others exist
                    $hasUnread = Message::where('conversation_id', $conversation->id)
                        ->whereNull('deleted_at')
                        ->whereHas('participant', function ($q) use ($userId, $userType) {
                            $q->where('participantable_id', '!=', $userId)
                                ->orWhere('participantable_type', '!=', $userType);
                        })
                        ->exists();
                }

                // Get chat group for name display
                $chatGroup = ChatGroup::where('conversation_id', $conversation->id)->first();

                return [
                    'id' => $conversation->id,
                    'name' => $chatGroup?->name
                        ?? ($conversation->isGroup()
                            ? $conversation->group?->name
                            : $otherParticipant?->participantable?->name),
                    'lastMessage' => $conversation->lastMessage?->body,
                    'unread' => $hasUnread,
                    'time' => $conversation->lastMessage?->created_at?->diffForHumans(),
                    'type' => $chatGroup?->type,
                ];
            })
            ->toArray();
    }

    /**
     * Get conversation IDs for chats where this user is the assigned supervisor.
     */
    protected function getSupervisedConversationIds(): array
    {
        $user = Auth::user();

        return ChatGroup::query()
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
