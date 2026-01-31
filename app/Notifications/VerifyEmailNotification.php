<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Email verification notification.
 *
 * Note: This runs synchronously (not queued) to avoid multi-tenancy context issues
 * and because users expect immediate delivery of verification emails.
 */
class VerifyEmailNotification extends Notification
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
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('تأكيد البريد الإلكتروني - '.$this->academy->name)
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'academy' => $this->academy,
                'verificationUrl' => $verificationUrl,
                'subject' => 'تأكيد البريد الإلكتروني - '.$this->academy->name,
            ]);
    }

    /**
     * Get the verification URL for the given notifiable.
     * Uses the academy's configured domain or falls back to subdomain routing.
     */
    protected function verificationUrl(object $notifiable): string
    {
        // Generate signed URL with proper base URL for production
        $expiration = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60));

        return URL::temporarySignedRoute(
            'verification.verify',
            $expiration,
            [
                'subdomain' => $this->academy->subdomain,
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'email_verification',
            'academy_id' => $this->academy->id,
        ];
    }
}
