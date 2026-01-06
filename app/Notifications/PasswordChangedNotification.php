<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a user's password is changed.
 *
 * Security: Notifies users of password changes so they can take action
 * if the change was unauthorized.
 */
class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        $message = (new MailMessage)
            ->subject('تم تغيير كلمة المرور - '.$this->academy->name)
            ->greeting('مرحباً '.($notifiable->first_name ?? $notifiable->name).'،')
            ->line('تم تغيير كلمة المرور الخاصة بحسابك في '.$this->academy->name.' بنجاح.')
            ->line('**تفاصيل التغيير:**')
            ->line('• التاريخ والوقت: '.now()->setTimezone('Asia/Riyadh')->format('Y-m-d H:i'));

        if ($this->ipAddress) {
            $message->line('• عنوان IP: '.$this->ipAddress);
        }

        $message->line('')
            ->line('**إذا لم تقم بهذا التغيير:**')
            ->line('يرجى التواصل مع فريق الدعم فوراً لتأمين حسابك.')
            ->action('تواصل مع الدعم', route('contact', ['subdomain' => $this->academy->subdomain]))
            ->line('')
            ->line('نصائح لأمان حسابك:')
            ->line('• لا تشارك كلمة المرور مع أي شخص')
            ->line('• استخدم كلمة مرور قوية ومختلفة لكل حساب')
            ->line('• قم بتفعيل المصادقة الثنائية إن توفرت')
            ->salutation('مع أطيب التحيات،'."\n".'فريق '.$this->academy->name);

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'academy_id' => $this->academy->id,
            'ip_address' => $this->ipAddress,
            'changed_at' => now()->toIso8601String(),
        ];
    }
}
