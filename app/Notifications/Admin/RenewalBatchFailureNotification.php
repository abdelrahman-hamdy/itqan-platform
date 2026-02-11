<?php

namespace App\Notifications\Admin;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alert notification sent to admins when bulk renewal failures occur.
 *
 * Triggered when more than 20% of auto-renewal attempts fail in a single batch.
 * Helps admins quickly identify and respond to systematic payment issues.
 */
class RenewalBatchFailureNotification extends Notification
{
    public function __construct(
        private readonly array $results,
        private readonly string $academyName = 'Itqan Academy'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $processed = $this->results['processed'] ?? 0;
        $successful = $this->results['successful'] ?? 0;
        $failed = $this->results['failed'] ?? 0;
        $failureRate = $processed > 0 ? round(($failed / $processed) * 100, 1) : 0;

        return (new MailMessage)
            ->subject('🚨 تحذير: معدل فشل مرتفع في تجديدات الاشتراكات - '.$this->academyName)
            ->greeting('السلام عليكم')
            ->error()
            ->line('**تنبيه عاجل:** تم اكتشاف معدل فشل مرتفع في عملية تجديد الاشتراكات التلقائية.')
            ->line('**التفاصيل:**')
            ->line('• **معدل الفشل:** '.$failureRate.'%')
            ->line('• **إجمالي المحاولات:** '.$processed)
            ->line('• **التجديدات الناجحة:** '.$successful)
            ->line('• **التجديدات الفاشلة:** '.$failed)
            ->line('')
            ->line('**الأسباب المحتملة:**')
            ->line('• بطاقات دفع منتهية الصلاحية')
            ->line('• عدم وجود بطاقات محفوظة للطلاب')
            ->line('• مشاكل في بوابة الدفع')
            ->line('• رصيد غير كافٍ في البطاقات')
            ->line('')
            ->action('عرض لوحة التحكم', url('/admin'))
            ->line('يرجى مراجعة لوحة التحكم للحصول على تفاصيل إضافية واتخاذ الإجراءات اللازمة.')
            ->line('')
            ->line('**نصائح للمتابعة:**')
            ->line('1. تحقق من حالة بوابة الدفع (Paymob)')
            ->line('2. راجع السجلات (logs) لمعرفة الأسباب التفصيلية')
            ->line('3. تواصل مع الطلاب المتأثرين لتحديث بطاقات الدفع')
            ->line('4. تحقق من مقاييس التجديد في لوحة التحكم')
            ->salutation('إشعار تلقائي من نظام '.$this->academyName);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'renewal_batch_failure',
            'results' => $this->results,
            'severity' => 'critical',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
