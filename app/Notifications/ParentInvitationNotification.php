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
        // Generate absolute URL including the academy subdomain — do NOT wrap with url()
        // since route() already generates an absolute URL for subdomain routes
        $resetUrl = route('password.reset', [
            'subdomain' => $academy->subdomain,
            'token' => $this->passwordResetToken,
            'email' => $notifiable->email,
        ]);

        // Load students once to avoid two separate DB queries
        $students = $this->parentProfile->students()->get();
        $studentsCount = $students->count();
        $studentNames = $students->pluck('full_name')->take(5)->join('، ');

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
