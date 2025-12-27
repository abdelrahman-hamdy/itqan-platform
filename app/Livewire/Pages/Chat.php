<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Title;
use Namu\WireChat\Livewire\Pages\Chat as WireChatChat;
use App\Enums\SessionStatus;

class Chat extends WireChatChat
{
    #[Title('المحادثة - منصة إتقان')]
    public function render()
    {
        return parent::render();
    }
}
