<?php

namespace App\Filament\Supervisor\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

class SupervisorInboxWidget extends Widget
{
    protected static bool $isDiscoverable = false;

    protected static string $view = 'filament.supervisor.widgets.inbox-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 1;

    public function getUnreadCount(): int
    {
        return Auth::user()->unreadMessagesCount();
    }

    public function getRecentConversations(): array
    {
        $user = Auth::user();
        $userId = $user->id;
        $userType = User::class;

        return Participant::query()
            ->where('participantable_id', $userId)
            ->where('participantable_type', $userType)
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
                        ->where(function ($q) use ($userId, $userType) {
                            $q->where('sendable_id', '!=', $userId)
                              ->orWhere('sendable_type', '!=', $userType);
                        })
                        ->exists();
                } else {
                    // Never read - check if any messages from others exist
                    $hasUnread = Message::where('conversation_id', $conversation->id)
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($userId, $userType) {
                            $q->where('sendable_id', '!=', $userId)
                              ->orWhere('sendable_type', '!=', $userType);
                        })
                        ->exists();
                }

                return [
                    'id' => $conversation->id,
                    'name' => $conversation->isGroup()
                        ? $conversation->group?->name
                        : $otherParticipant?->participantable?->name,
                    'lastMessage' => $conversation->lastMessage?->body,
                    'unread' => $hasUnread,
                    'time' => $conversation->lastMessage?->created_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }

    public function getChatUrl(): string
    {
        $academy = Auth::user()->academy;
        $subdomain = $academy?->subdomain ?? 'default';

        return route('chats', ['subdomain' => $subdomain]);
    }
}
