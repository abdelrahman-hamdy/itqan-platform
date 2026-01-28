<?php

namespace App\Notifications;

use App\Models\Academy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $actionUrl,
        private readonly Academy $academy
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fromAddress = $this->academy->email ?: config('mail.from.address');
        $fromName = $this->academy->getEmailFromName();

        $mail = (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject($this->title.' - '.$this->academy->localized_name)
            ->greeting('مرحباً '.($notifiable->name ?? ''))
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('عرض التفاصيل', $this->actionUrl);
        }

        $mail->line('هذا إشعار تلقائي من '.$this->academy->localized_name);

        return $mail;
    }
}
