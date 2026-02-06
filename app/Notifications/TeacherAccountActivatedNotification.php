<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to teachers when their account is activated by admin.
 *
 * Note: Runs synchronously to avoid multi-tenancy context issues
 * and because teachers expect immediate notification of activation.
 */
class TeacherAccountActivatedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Academy $academy
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = $this->getLoginUrl();

        return (new MailMessage)
            ->subject('تم تفعيل حسابك - '.$this->academy->name)
            ->view('emails.teacher-account-activated', [
                'user' => $notifiable,
                'academy' => $this->academy,
                'loginUrl' => $loginUrl,
                'subject' => 'تم تفعيل حسابك - '.$this->academy->name,
            ]);
    }

    /**
     * Get the login URL for the academy.
     */
    protected function getLoginUrl(): string
    {
        return route('login', ['subdomain' => $this->academy->subdomain]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'teacher_account_activated',
            'academy_id' => $this->academy->id,
        ];
    }
}
