<?php

namespace App\Notifications;

use App\Models\ParentProfile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Parent invitation notification.
 *
 * Note: Runs synchronously to avoid multi-tenancy context issues
 * and because parents expect immediate delivery of invitations.
 */
class ParentInvitationNotification extends Notification
{

    public function __construct(
        public ParentProfile $parentProfile,
        public string $passwordResetToken
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
        $academy = $this->parentProfile->academy;
        $resetUrl = url(route('password.reset', [
            'subdomain' => $academy->subdomain,
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
            ->subject('مرحباً بك في '.$academy->name)
            ->view('emails.parent-invitation', [
                'user' => $notifiable,
                'academy' => $academy,
                'parentProfile' => $this->parentProfile,
                'resetUrl' => $resetUrl,
                'studentsCount' => $studentsCount,
                'studentNames' => $studentNames,
                'subject' => 'مرحباً بك في '.$academy->name,
            ]);
    }
}
