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
        $chatGroup = ChatGroup::find($chatGroupId);

        if ($chatGroup && $chatGroup->canBeArchivedBy(auth()->user())) {
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
        // Find or create ChatGroup for this conversation
        $chatGroup = ChatGroup::firstOrCreate(
            ['conversation_id' => $conversationId],
            [
                'type' => ChatGroup::TYPE_SUPERVISED_INDIVIDUAL,
                'name' => 'Supervised Chat',
                'is_active' => true,
            ]
        );

        if ($chatGroup && $chatGroup->canBeArchivedBy(auth()->user())) {
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
        $chatGroup = ChatGroup::find($chatGroupId);

        if ($chatGroup && $chatGroup->canBeArchivedBy(auth()->user())) {
            $chatGroup->unarchive();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('chat.chat_unarchived'),
            ]);
        }
    }
}
