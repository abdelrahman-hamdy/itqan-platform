<?php

namespace App\Livewire\Chat;

use Wirechat\Wirechat\Livewire\Chats\Chats as BaseChats;

/**
 * Custom Chats component that fixes the real-time sidebar refresh issue.
 *
 * The vendor's loadConversations() uses concat + unique('id') which keeps
 * the first (stale) occurrence when conversations are re-fetched.
 * This override ensures all refresh paths properly reset the collection.
 */
class Chats extends BaseChats
{
    /**
     * Override getListeners to map 'refresh' to our handleRefresh method
     * instead of the built-in '$refresh' which re-renders with stale data.
     */
    public function getListeners()
    {
        $listeners = parent::getListeners();

        // Replace '$refresh' with our method that resets conversations
        if (isset($listeners['refresh']) && $listeners['refresh'] === '$refresh') {
            $listeners['refresh'] = 'handleRefresh';
        }

        return $listeners;
    }

    /**
     * Handle the 'refresh' event by resetting conversations.
     *
     * Called when: Chat component sends/receives messages, deletes messages, etc.
     * The parent's '$refresh' just re-renders, but loadConversations() concat +
     * unique('id') keeps stale data. We reset the collection so it loads fresh.
     */
    public function handleRefresh()
    {
        $this->refreshChats();
    }

    /**
     * Override to properly reload conversations when a broadcast arrives.
     *
     * Called when: NotifyParticipant echo event fires for a non-selected conversation.
     * The parent dispatches 'refresh' → '$refresh', we call refreshChats() directly.
     */
    public function refreshComponent($event)
    {
        if ($event['message']['conversation_id'] != $this->selectedConversationId) {
            $this->refreshChats();
        }
    }
}
