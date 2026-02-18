<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Title;
use Wirechat\Wirechat\Livewire\Pages\Chats as WireChatChats;

class Chats extends WireChatChats
{
    #[Title('المحادثات - منصة إتقان')]
    public function render()
    {
        return parent::render();
    }
}
