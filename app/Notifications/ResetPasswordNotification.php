<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password reset notification.
 *
 * Note: Runs synchronously to avoid multi-tenancy context issues
 * and because users expect immediate delivery.
 */
class ResetPasswordNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $token,
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
        $resetUrl = route('password.reset', [
            'subdomain' => $this->academy->subdomain,
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور - '.$this->academy->name)
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'academy' => $this->academy,
                'resetUrl' => $resetUrl,
                'subject' => 'إعادة تعيين كلمة المرور - '.$this->academy->name,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_reset',
            'academy_id' => $this->academy->id,
        ];
    }
}
