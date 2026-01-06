<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->greeting('مرحباً '.$notifiable->first_name.'،')
            ->line('لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في '.$this->academy->name.'.')
            ->line('اضغط على الزر أدناه لإعادة تعيين كلمة المرور:')
            ->action('إعادة تعيين كلمة المرور', $resetUrl)
            ->line('هذا الرابط صالح لمدة 60 دقيقة فقط.')
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.')
            ->salutation('مع أطيب التحيات،'."\n".'فريق '.$this->academy->name);
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
