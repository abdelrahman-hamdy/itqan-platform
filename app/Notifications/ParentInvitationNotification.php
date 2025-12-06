<?php

namespace App\Notifications;

use App\Models\ParentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class ParentInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ParentProfile $parentProfile,
        public string $passwordResetToken
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
        $academy = $this->parentProfile->academy;
        $resetUrl = url(route('password.reset', [
            'token' => $this->passwordResetToken,
            'email' => $notifiable->email,
        ], false));

        $studentsCount = $this->parentProfile->students()->count();
        $studentNames = $this->parentProfile->students()
            ->get()
            ->pluck('full_name')
            ->take(5)
            ->join('، ');

        return (new MailMessage)
            ->subject('مرحباً بك في أكاديمية ' . $academy->name)
            ->greeting('مرحباً ' . $this->parentProfile->full_name . '،')
            ->line('تم إنشاء حساب ولي أمر لك في أكاديمية ' . $academy->name . '.')
            ->line('**رمز ولي الأمر:** ' . $this->parentProfile->parent_code)
            ->line('**البريد الإلكتروني:** ' . $notifiable->email)
            ->when($studentsCount > 0, function ($mail) use ($studentsCount, $studentNames) {
                return $mail->line('**الطلاب المرتبطون:** ' . $studentNames . ($studentsCount > 5 ? ' وآخرون' : ''));
            })
            ->line('لتفعيل حسابك، يرجى تعيين كلمة مرور جديدة بالضغط على الزر أدناه:')
            ->action('تعيين كلمة المرور', $resetUrl)
            ->line('رابط تعيين كلمة المرور صالح لمدة 48 ساعة.')
            ->line('بعد تعيين كلمة المرور، يمكنك تسجيل الدخول باستخدام بريدك الإلكتروني وكلمة المرور الجديدة.')
            ->salutation('مع أطيب التحيات،' . "\n" . 'فريق ' . $academy->name);
    }
}
