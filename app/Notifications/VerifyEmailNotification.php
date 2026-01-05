<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Academy $academy
    ) {
    }

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
            ->subject('تأكيد البريد الإلكتروني - ' . $this->academy->name)
            ->greeting('مرحباً ' . $notifiable->first_name . '،')
            ->line('شكراً لتسجيلك في ' . $this->academy->name . '.')
            ->line('يرجى الضغط على الزر أدناه لتأكيد بريدك الإلكتروني:')
            ->action('تأكيد البريد الإلكتروني', $verificationUrl)
            ->line('هذا الرابط صالح لمدة 60 دقيقة.')
            ->line('إذا لم تقم بإنشاء حساب، يمكنك تجاهل هذا البريد الإلكتروني.')
            ->salutation('مع أطيب التحيات،' . "\n" . 'فريق ' . $this->academy->name);
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
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
