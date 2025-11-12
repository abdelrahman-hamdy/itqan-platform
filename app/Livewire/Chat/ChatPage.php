<?php

namespace App\Livewire\Chat;

use Livewire\Attributes\Title;
use Livewire\Component;
use Namu\WireChat\Models\Conversation;

class ChatPage extends Component
{
    public $conversation;

    public function mount()
    {
        // Make sure user is authenticated
        abort_unless(auth()->check(), 401);

        // Remove deleted conversation in case the user decides to visit the deleted conversation
        $this->conversation = Conversation::where('id', $this->conversation)->firstOrFail();

        // Check if the user belongs to the conversation
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
    }

    #[Title('المحادثة')]
    public function render()
    {
        return view('wirechat::livewire.pages.chat')
            ->layout('chat.unified');
    }
}
