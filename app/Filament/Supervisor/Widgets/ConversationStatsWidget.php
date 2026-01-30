<?php

namespace App\Filament\Supervisor\Widgets;

use App\Models\ChatGroup;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

/**
 * Conversation Stats Widget for Supervisor Panel
 *
 * Displays 4 key metrics about supervised conversations:
 * - Total supervised chats
 * - Unread messages count
 * - Active conversations today
 * - Archived conversations
 */
class ConversationStatsWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected static bool $isDiscoverable = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 1;

    protected function getHeading(): ?string
    {
        return 'إحصائيات المحادثات';
    }

    /**
     * Cache stats for 5 minutes to reduce queries.
     */
    protected function getCacheKey(): string
    {
        return 'supervisor_conversation_stats_'.Auth::id();
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        // Cache for dashboard stats duration
        $stats = Cache::remember($this->getCacheKey(), config('business.cache.dashboard_ttl', 1800), function () use ($user) {
            return $this->calculateStats($user);
        });

        return [
            Stat::make(__('chat.total_supervised_chats'), $stats['total'])
                ->description(__('chat.supervised_chats_description'))
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('primary'),

            Stat::make(__('chat.unread_messages'), $stats['unread'])
                ->description(__('chat.messages_awaiting'))
                ->descriptionIcon('heroicon-o-envelope')
                ->color($stats['unread'] > 0 ? 'danger' : 'success'),
        ];
    }

    protected function calculateStats(User $user): array
    {
        // Get all supervised chat groups for this user
        $supervisedGroups = ChatGroup::query()
            ->where('supervisor_id', $user->id)
            ->whereNotNull('conversation_id')
            ->get();

        $total = $supervisedGroups->count();
        $activeCount = $supervisedGroups->where('archived_at', null)->count();
        $archivedCount = $supervisedGroups->whereNotNull('archived_at')->count();

        // Get conversation IDs for active supervised groups
        $activeConversationIds = $supervisedGroups
            ->where('archived_at', null)
            ->pluck('conversation_id')
            ->toArray();

        // Calculate unread messages
        $unread = 0;
        if (! empty($activeConversationIds)) {
            $participants = Participant::query()
                ->where('participantable_id', $user->id)
                ->where('participantable_type', User::class)
                ->whereIn('conversation_id', $activeConversationIds)
                ->get();

            foreach ($participants as $p) {
                if ($p->conversation_read_at) {
                    $unread += Message::where('conversation_id', $p->conversation_id)
                        ->whereNull('deleted_at')
                        ->where('created_at', '>', $p->conversation_read_at)
                        ->where(function ($q) use ($user) {
                            $q->where('sendable_id', '!=', $user->id)
                                ->orWhere('sendable_type', '!=', User::class);
                        })
                        ->count();
                } else {
                    $unread += Message::where('conversation_id', $p->conversation_id)
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($user) {
                            $q->where('sendable_id', '!=', $user->id)
                                ->orWhere('sendable_type', '!=', User::class);
                        })
                        ->count();
                }
            }
        }

        // Count conversations with activity today
        $activeToday = 0;
        if (! empty($activeConversationIds)) {
            $activeToday = Message::whereIn('conversation_id', $activeConversationIds)
                ->whereNull('deleted_at')
                ->whereDate('created_at', today())
                ->distinct('conversation_id')
                ->count('conversation_id');
        }

        return [
            'total' => $activeCount,
            'unread' => $unread,
            'activeToday' => $activeToday,
            'archived' => $archivedCount,
        ];
    }

    /**
     * Clear cached stats when data changes.
     */
    public static function clearCache(int $supervisorId): void
    {
        Cache::forget('supervisor_conversation_stats_'.$supervisorId);
    }
}
