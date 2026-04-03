<?php

namespace App\Notifications;

use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CircleTeacherChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public QuranIndividualCircle $circle,
        public ?User $oldTeacher,
        public User $newTeacher,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $oldName = $this->oldTeacher?->name ?? '-';
        $newName = $this->newTeacher->name;

        return [
            'type' => 'circle_teacher_changed',
            'title' => __('notifications.circle_teacher_changed.title'),
            'message' => __('notifications.circle_teacher_changed.message', [
                'circle_name' => $this->circle->name ?? $this->circle->circle_code,
                'old_teacher_name' => $oldName,
                'new_teacher_name' => $newName,
            ]),
            'circle_id' => $this->circle->id,
        ];
    }
}
