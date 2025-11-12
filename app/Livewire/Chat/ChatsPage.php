<?php

namespace App\Livewire\Chat;

use Livewire\Attributes\Title;
use Livewire\Component;

class ChatsPage extends Component
{
    #[Title('المحادثات')]
    public function render()
    {
        return view('wirechat::livewire.pages.chats')
            ->layout('chat.unified');
    }
}
