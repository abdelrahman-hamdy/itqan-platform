<?php

namespace App\Livewire\Pages;

use App\Models\ChatGroup;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Wirechat\Wirechat\Livewire\Pages\Chat as WireChatChat;

class Chat extends WireChatChat
{
    #[Title('المحادثة - منصة إتقان')]
    public function render()
    {
        return parent::render();
    }

    /**
     * Archive a chat by ChatGroup ID
     */
    #[On('archiveChat')]
    public function archiveChat(int $chatGroupId): void
    {
        $user = auth()->user();
        $chatGroup = ChatGroup::find($chatGroupId);

        if (! $chatGroup) {
            return;
        }

        // Tenant isolation: ensure the chat group belongs to the current user's academy
        if ($chatGroup->academy_id !== $user?->academy_id) {
            return;
        }

        if ($chatGroup->canBeArchivedBy($user)) {
            $chatGroup->archive();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('chat.chat_archived'),
            ]);
            // Redirect to chats list
            $this->redirect(route('chats'));
        }
    }

    /**
     * Archive a chat by creating ChatGroup from conversation ID (for chats without existing ChatGroup)
     */
    #[On('archiveChatByConversation')]
    public function archiveChatByConversation(int $conversationId): void
    {
        // Verify the current user is a participant in this conversation BEFORE creating/touching records
        $user = auth()->user();
        $isParticipant = \Namu\WireChat\Models\Conversation::where('id', $conversationId)
            ->whereHas('participants', fn ($q) => $q->where('participantable_id', $user->id)->where('participantable_type', get_class($user)))
            ->exists();

        if (! $isParticipant) {
            return;
        }

        // Find or create ChatGroup for this conversation
        $chatGroup = ChatGroup::firstOrCreate(
            ['conversation_id' => $conversationId],
            [
                'academy_id' => $user?->academy_id,
                'type' => ChatGroup::TYPE_SUPERVISED_INDIVIDUAL,
                'name' => 'Supervised Chat',
                'is_active' => true,
            ]
        );

        // Tenant isolation: ensure the chat group belongs to the current user's academy
        if ($chatGroup->academy_id !== $user?->academy_id) {
            return;
        }

        if ($chatGroup->canBeArchivedBy($user)) {
            $chatGroup->archive();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('chat.chat_archived'),
            ]);
            // Redirect to chats list
            $this->redirect(route('chats'));
        }
    }

    /**
     * Unarchive a chat
     */
    #[On('unarchiveChat')]
    public function unarchiveChat(int $chatGroupId): void
    {
        $user = auth()->user();
        $chatGroup = ChatGroup::find($chatGroupId);

        if (! $chatGroup) {
            return;
        }

        // Tenant isolation: ensure the chat group belongs to the current user's academy
        if ($chatGroup->academy_id !== $user?->academy_id) {
            return;
        }

        if ($chatGroup->canBeArchivedBy($user)) {
            $chatGroup->unarchive();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('chat.chat_unarchived'),
            ]);
        }
    }
}
