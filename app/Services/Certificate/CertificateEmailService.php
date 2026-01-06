<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Notifications\CertificateIssuedNotification;
use App\Services\ParentNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CertificateEmailService
{
    public function __construct(
        protected ParentNotificationService $parentNotificationService
    ) {}

    /**
     * Send certificate notification to student
     */
    public function sendCertificate(Certificate $certificate): bool
    {
        try {
            $student = $certificate->student;
            $student->notify(new CertificateIssuedNotification($certificate));

            Log::info('Certificate notification sent to student', [
                'certificate_id' => $certificate->id,
                'student_id' => $student->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Certificate notification failed', [
                'certificate_id' => $certificate->id,
                'student_id' => $certificate->student_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send certificate notification to parents
     */
    public function sendToParents(Certificate $certificate): bool
    {
        try {
            $this->parentNotificationService->sendCertificateIssued($certificate);

            Log::info('Certificate notification sent to parents', [
                'certificate_id' => $certificate->id,
                'student_id' => $certificate->student_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send parent certificate notification', [
                'certificate_id' => $certificate->id,
                'student_id' => $certificate->student_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send certificate to both student and parents
     */
    public function sendToAll(Certificate $certificate): array
    {
        $results = [
            'student' => false,
            'parents' => false,
        ];

        // Send to student
        $results['student'] = $this->sendCertificate($certificate);

        // Send to parents
        $results['parents'] = $this->sendToParents($certificate);

        return $results;
    }

    /**
     * Queue certificate email for delayed sending
     */
    public function queueCertificateEmail(Certificate $certificate, ?int $delayInMinutes = null): void
    {
        $notification = new CertificateIssuedNotification($certificate);

        if ($delayInMinutes) {
            $certificate->student->notify($notification->delay(now()->addMinutes($delayInMinutes)));
        } else {
            $certificate->student->notify($notification);
        }

        Log::info('Certificate email queued', [
            'certificate_id' => $certificate->id,
            'student_id' => $certificate->student_id,
            'delay_minutes' => $delayInMinutes,
        ]);
    }

    /**
     * Resend certificate notification
     */
    public function resendCertificate(Certificate $certificate): bool
    {
        Log::info('Resending certificate notification', [
            'certificate_id' => $certificate->id,
            'student_id' => $certificate->student_id,
        ]);

        return $this->sendCertificate($certificate);
    }

    /**
     * Send bulk certificates to multiple students
     */
    public function sendBulkCertificates(array $certificates): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($certificates as $certificate) {
            if ($this->sendCertificate($certificate)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'certificate_id' => $certificate->id,
                    'student_id' => $certificate->student_id,
                ];
            }
        }

        Log::info('Bulk certificate sending completed', $results);

        return $results;
    }

    /**
     * Send certificate with custom email template
     */
    public function sendWithCustomTemplate(Certificate $certificate, string $template, array $data = []): bool
    {
        try {
            $student = $certificate->student;

            Mail::send($template, array_merge($data, [
                'certificate' => $certificate,
                'student' => $student,
            ]), function ($message) use ($student, $certificate) {
                $message->to($student->email)
                    ->subject('شهادة جديدة - '.$certificate->certificate_number);

                // Attach PDF if exists
                if ($certificate->fileExists()) {
                    $message->attach(Storage::path($certificate->file_path), [
                        'as' => $certificate->certificate_number.'.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            Log::info('Certificate sent with custom template', [
                'certificate_id' => $certificate->id,
                'student_id' => $student->id,
                'template' => $template,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send certificate with custom template', [
                'certificate_id' => $certificate->id,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
