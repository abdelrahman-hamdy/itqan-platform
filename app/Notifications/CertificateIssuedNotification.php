<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Certificate $certificate;

    /**
     * Create a new notification instance.
     */
    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $certificateTypeName = $this->certificate->certificate_type->label();

        return (new MailMessage)
            ->subject('🎓 تم إصدار شهادتك - Certificate Issued')
            ->greeting('مرحباً '.$notifiable->name)
            ->line('تهانينا! تم إصدار شهادة لك في '.$certificateTypeName)
            ->line('رقم الشهادة: '.$this->certificate->certificate_number)
            ->line('تاريخ الإصدار: '.$this->certificate->issued_at->locale('ar')->translatedFormat('d F Y'))
            ->action('عرض وتحميل الشهادة', $this->certificate->download_url)
            ->line('يمكنك تحميل شهادتك في أي وقت من لوحة التحكم الخاصة بك.')
            ->line('مبروك على إنجازك!');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'certificate_issued',
            'certificate_id' => $this->certificate->id,
            'certificate_number' => $this->certificate->certificate_number,
            'certificate_type' => $this->certificate->certificate_type->value,
            'certificate_type_label' => $this->certificate->certificate_type->label(),
            'issued_at' => $this->certificate->issued_at->toDateTimeString(),
            'download_url' => $this->certificate->download_url,
            'view_url' => $this->certificate->view_url,
            'title' => 'تم إصدار شهادتك',
            'message' => 'تهانينا! تم إصدار شهادة '.$this->certificate->certificate_type->label().' لك.',
            'action_url' => $this->certificate->download_url,
            'action_text' => 'عرض الشهادة',
        ];
    }
}
