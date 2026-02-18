<?php

namespace App\Livewire\Chat;

use Illuminate\Support\Collection;
use Wirechat\Wirechat\Livewire\Chat\Info as BaseInfo;

/**
 * @property Collection $mediaAttachments
 * @property Collection $fileAttachments
 */
class Info extends BaseInfo
{
    public function getMediaAttachmentsProperty()
    {
        return $this->conversation->messages()
            ->with('attachment')
            ->whereHas('attachment', function ($query) {
                $query->where(function ($q) {
                    $q->where('mime_type', 'LIKE', 'image/%')
                        ->orWhere('mime_type', 'LIKE', 'video/%');
                });
            })
            ->latest()
            ->get()
            ->map(fn ($message) => $message->attachment)
            ->filter();
    }

    public function getFileAttachmentsProperty()
    {
        return $this->conversation->messages()
            ->with('attachment')
            ->whereHas('attachment', function ($query) {
                $query->where(function ($q) {
                    $q->where('mime_type', 'NOT LIKE', 'image/%')
                        ->where('mime_type', 'NOT LIKE', 'video/%');
                });
            })
            ->latest()
            ->get()
            ->map(fn ($message) => $message->attachment)
            ->filter();
    }

    public function render()
    {
        $receiver = $this->conversation->peerParticipant(auth()->user())?->participantable;

        // Pass data to the view
        return view('wirechat::livewire.chat.info', [
            'receiver' => $receiver,
            'cover_url' => $receiver?->cover_url,
            'mediaAttachments' => $this->mediaAttachments,
            'fileAttachments' => $this->fileAttachments,
        ]);
    }
}
