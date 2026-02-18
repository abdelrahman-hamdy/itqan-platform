<?php

namespace App\Livewire\Chat;

use Wirechat\Wirechat\Livewire\Chats\Chats as BaseChats;

/**
 * Custom Chats component that fixes the real-time sidebar refresh issue.
 *
 * The vendor refreshComponent() dispatches '$refresh' which re-renders
 * but keeps stale conversation data (unique('id') preserves first/stale occurrence).
 * This override calls refreshChats() which properly resets the collection.
 */
class Chats extends BaseChats
{
    /**
     * Override to properly reload conversations when a new message arrives.
     *
     * The parent dispatches 'refresh' → '$refresh' which re-renders but
     * loadConversations() concat + unique('id') keeps stale data.
     * refreshChats() resets the collection so loadConversations() starts fresh.
     */
    public function refreshComponent($event)
    {
        if ($event['message']['conversation_id'] != $this->selectedConversationId) {
            $this->refreshChats();
        }
    }
}
