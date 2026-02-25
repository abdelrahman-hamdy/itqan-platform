<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a user's password is changed.
 *
 * Security: Notifies users of password changes so they can take action
 * if the change was unauthorized.
 *
 * Note: Runs synchronously to avoid multi-tenancy context issues
 * and because security notifications should be immediate.
 */
class PasswordChangedNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Academy $academy,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
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
        return (new MailMessage)
            ->subject('تم تغيير كلمة المرور - '.$this->academy->name)
            ->view('emails.password-changed', [
                'user' => $notifiable,
                'academy' => $this->academy,
                'ipAddress' => htmlspecialchars($this->ipAddress ?? 'unknown', ENT_QUOTES, 'UTF-8'),
                'subject' => 'تم تغيير كلمة المرور - '.$this->academy->name,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'academy_id' => $this->academy->id,
            'ip_address' => htmlspecialchars($this->ipAddress ?? 'unknown', ENT_QUOTES, 'UTF-8'),
            'changed_at' => now()->toIso8601String(),
        ];
    }
}
