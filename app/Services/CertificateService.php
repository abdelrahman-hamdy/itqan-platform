<?php

namespace App\Services;

use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Models\AcademicSubscription;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate a unique certificate number
     */
    public function generateCertificateNumber(): string
    {
        $year = now()->year;
        $random = strtoupper(Str::random(6));

        // Ensure uniqueness
        while (Certificate::where('certificate_number', "CERT-{$year}-{$random}")->exists()) {
            $random = strtoupper(Str::random(6));
        }

        return "CERT-{$year}-{$random}";
    }

    /**
     * Issue certificate for a recorded course completion
     */
    public function issueCertificateForRecordedCourse(CourseSubscription $subscription): Certificate
    {
        // Check if certificate already issued
        if ($subscription->certificate_issued || $subscription->certificate()->exists()) {
            return $subscription->certificate;
        }

        // Verify completion
        if ($subscription->progress_percentage < 100) {
            throw new \Exception('Student must complete 100% of the course to receive a certificate.');
        }

        $course = $subscription->recordedCourse;
        $academy = $subscription->academy;

        // Get certificate template text (from course or academy settings)
        $templateText = $course->certificate_template_text
            ?? $this->getDefaultTemplateText($academy, CertificateType::RECORDED_COURSE);

        // Get template style
        $templateStyle = $course->certificate_template_style
            ?? $academy->settings->getSetting('certificates.default_template_style', 'modern');

        // Replace placeholders in template text
        $certificateText = $this->replacePlaceholders($templateText, [
            'student_name' => $subscription->student->name,
            'course_name' => $course->title,
            'completion_date' => $subscription->completion_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'academy_name' => $academy->name_ar,
        ]);

        // Create certificate record
        $certificate = $this->createCertificate([
            'academy_id' => $academy->id,
            'student_id' => $subscription->student_id,
            'teacher_id' => null,
            'certificateable_type' => CourseSubscription::class,
            'certificateable_id' => $subscription->id,
            'certificate_type' => CertificateType::RECORDED_COURSE,
            'template_style' => $templateStyle,
            'certificate_text' => $certificateText,
            'is_manual' => false,
            'issued_by' => null,
            'metadata' => [
                'completion_percentage' => $subscription->progress_percentage,
                'final_score' => $subscription->final_score,
                'course_code' => $course->course_code,
            ],
        ]);

        // Update subscription
        $subscription->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
            'completion_certificate_url' => $certificate->download_url,
        ]);

        // Send notification
        $subscription->student->notify(new CertificateIssuedNotification($certificate));

        return $certificate;
    }

    /**
     * Issue certificate for an interactive course
     */
    public function issueCertificateForInteractiveCourse(InteractiveCourseEnrollment $enrollment): Certificate
    {
        // Check if certificate already issued
        if ($enrollment->certificate_issued || $enrollment->certificate()->exists()) {
            return $enrollment->certificate;
        }

        // Verify completion
        if ($enrollment->enrollment_status !== 'completed') {
            throw new \Exception('Student must complete the course to receive a certificate.');
        }

        $course = $enrollment->course;
        $academy = $enrollment->academy;
        $teacher = $course->assignedTeacher;

        // Get template style and text
        $templateStyle = $course->certificate_template_style
            ?? $academy->settings->getSetting('certificates.default_template_style', 'modern');

        $templateText = $this->getDefaultTemplateText($academy, CertificateType::INTERACTIVE_COURSE);

        $certificateText = $this->replacePlaceholders($templateText, [
            'student_name' => $enrollment->student->user->name ?? $enrollment->student->full_name,
            'course_name' => $course->title,
            'completion_date' => now()->format('Y-m-d'),
            'teacher_name' => $teacher->user->name ?? '',
            'academy_name' => $academy->name_ar,
        ]);

        // Create certificate
        $certificate = $this->createCertificate([
            'academy_id' => $academy->id,
            'student_id' => $enrollment->student_id,
            'teacher_id' => $teacher?->user_id,
            'certificateable_type' => InteractiveCourseEnrollment::class,
            'certificateable_id' => $enrollment->id,
            'certificate_type' => CertificateType::INTERACTIVE_COURSE,
            'template_style' => $templateStyle,
            'certificate_text' => $certificateText,
            'is_manual' => false,
            'issued_by' => null,
            'metadata' => [
                'completion_percentage' => $enrollment->completion_percentage,
                'final_grade' => $enrollment->final_grade,
                'attendance_percentage' => $enrollment->getAttendancePercentage(),
                'course_code' => $course->course_code,
            ],
        ]);

        // Update enrollment
        $enrollment->update([
            'certificate_issued' => true,
        ]);

        // Send notification
        if ($enrollment->student->user) {
            $enrollment->student->user->notify(new CertificateIssuedNotification($certificate));
        }

        return $certificate;
    }

    /**
     * Issue manual certificate (for Quran or Academic subscriptions)
     */
    public function issueManualCertificate(
        $subscriptionable,
        string $achievementText,
        CertificateTemplateStyle|string $templateStyle,
        ?int $issuedBy = null,
        ?int $teacherId = null
    ): Certificate {
        // Ensure it's a QuranSubscription or AcademicSubscription
        if (!($subscriptionable instanceof QuranSubscription) &&
            !($subscriptionable instanceof AcademicSubscription)) {
            throw new \Exception('Invalid subscription type for manual certificate.');
        }

        // Check if certificate already issued
        if ($subscriptionable->certificate_issued || $subscriptionable->certificate()->exists()) {
            throw new \Exception('Certificate already issued for this subscription.');
        }

        $academy = $subscriptionable->academy;
        $student = $subscriptionable->student;

        // Determine certificate type
        $certificateType = $subscriptionable instanceof QuranSubscription
            ? CertificateType::QURAN_SUBSCRIPTION
            : CertificateType::ACADEMIC_SUBSCRIPTION;

        // Get template text
        $templateText = $this->getDefaultTemplateText($academy, $certificateType);

        // Get teacher
        if (!$teacherId && $subscriptionable instanceof QuranSubscription) {
            $teacherId = $subscriptionable->quran_teacher_id;
        } elseif (!$teacherId && $subscriptionable instanceof AcademicSubscription) {
            $teacherId = $subscriptionable->teacher?->user_id;
        }

        $teacherUser = $teacherId ? User::find($teacherId) : null;

        // Replace placeholders
        $certificateText = $this->replacePlaceholders($templateText, [
            'student_name' => $student->name,
            'achievement' => $achievementText,
            'teacher_name' => $teacherUser?->name ?? '',
            'academy_name' => $academy->name_ar,
            'completion_date' => now()->format('Y-m-d'),
        ]);

        // Convert template style to enum if string
        if (is_string($templateStyle)) {
            $templateStyle = CertificateTemplateStyle::from($templateStyle);
        }

        // Create certificate
        $certificate = $this->createCertificate([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'teacher_id' => $teacherId,
            'certificateable_type' => get_class($subscriptionable),
            'certificateable_id' => $subscriptionable->id,
            'certificate_type' => $certificateType,
            'template_style' => $templateStyle,
            'certificate_text' => $certificateText,
            'custom_achievement_text' => $achievementText,
            'is_manual' => true,
            'issued_by' => $issuedBy,
            'metadata' => [
                'subscription_code' => $subscriptionable->subscription_code ?? null,
            ],
        ]);

        // Update subscription
        $subscriptionable->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
        ]);

        // Send notification
        $student->notify(new CertificateIssuedNotification($certificate));

        return $certificate;
    }

    /**
     * Create certificate record and generate PDF
     */
    protected function createCertificate(array $data): Certificate
    {
        // Generate certificate number
        $data['certificate_number'] = $this->generateCertificateNumber();
        $data['issued_at'] = now();

        // Create certificate record (without file_path first)
        $data['file_path'] = 'temp'; // Temporary placeholder
        $certificate = Certificate::create($data);

        // Generate and store PDF
        $pdf = $this->generateCertificatePDF($certificate);
        $filePath = $this->storeCertificatePDF($pdf, $certificate);

        // Update certificate with actual file path
        $certificate->update(['file_path' => $filePath]);

        return $certificate->fresh();
    }

    /**
     * Generate certificate PDF
     */
    public function generateCertificatePDF(Certificate $certificate)
    {
        $data = $this->getCertificateData($certificate);
        $viewPath = $certificate->template_style->viewPath();

        return Pdf::loadView($viewPath, $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Get certificate data for PDF generation
     */
    public function getCertificateData(Certificate $certificate): array
    {
        $academy = $certificate->academy;
        $student = $certificate->student;
        $teacher = $certificate->teacher;

        // Get academy settings
        $settings = $academy->settings ?? $academy->getOrCreateSettings();
        $signatureName = $settings->getSetting('certificates.signature_name', 'المدير التنفيذي');
        $signatureTitle = $settings->getSetting('certificates.signature_title', 'المدير التنفيذي');

        return [
            'certificate' => $certificate,
            'academy' => $academy,
            'student' => $student,
            'teacher' => $teacher,
            'certificate_number' => $certificate->certificate_number,
            'certificate_text' => $certificate->certificate_text,
            'issued_date' => $certificate->issued_at->format('Y-m-d'),
            'issued_date_formatted' => $certificate->issued_at->locale('ar')->translatedFormat('d F Y'),
            'academy_logo' => $academy->logo ?? null,
            'academy_name' => $academy->name_ar,
            'student_name' => $student->name,
            'teacher_name' => $teacher?->name ?? '',
            'signature_name' => $signatureName,
            'signature_title' => $signatureTitle,
            'template_style' => $certificate->template_style,
            'metadata' => $certificate->metadata ?? [],
        ];
    }

    /**
     * Store certificate PDF to storage
     */
    protected function storeCertificatePDF($pdf, Certificate $certificate): string
    {
        $academy = $certificate->academy;
        $year = now()->year;
        $type = $certificate->certificate_type->value;

        // Build file path
        $fileName = "{$certificate->student_id}_{$certificate->certificate_number}.pdf";
        $directory = "tenants/{$academy->id}/certificates/{$year}/{$type}";
        $filePath = "{$directory}/{$fileName}";

        // Save PDF
        Storage::put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Preview certificate without saving
     */
    public function previewCertificate(
        array $data,
        CertificateTemplateStyle|string $templateStyle
    ) {
        if (is_string($templateStyle)) {
            $templateStyle = CertificateTemplateStyle::from($templateStyle);
        }

        $viewPath = $templateStyle->viewPath();

        return Pdf::loadView($viewPath, $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(Certificate $certificate)
    {
        if (!$certificate->fileExists()) {
            // Regenerate if file doesn't exist
            $pdf = $this->generateCertificatePDF($certificate);
            $filePath = $this->storeCertificatePDF($pdf, $certificate);
            $certificate->update(['file_path' => $filePath]);
        }

        return Storage::download(
            $certificate->file_path,
            "{$certificate->certificate_number}.pdf"
        );
    }

    /**
     * Stream certificate (view in browser)
     */
    public function streamCertificate(Certificate $certificate)
    {
        if (!$certificate->fileExists()) {
            // Regenerate if file doesn't exist
            $pdf = $this->generateCertificatePDF($certificate);
            return $pdf->stream("{$certificate->certificate_number}.pdf");
        }

        return response()->file(Storage::path($certificate->file_path));
    }

    /**
     * Revoke certificate
     */
    public function revokeCertificate(Certificate $certificate): bool
    {
        // Soft delete the certificate
        $certificate->delete();

        // Update the related subscription
        $certificateable = $certificate->certificateable;
        if ($certificateable) {
            $certificateable->update([
                'certificate_issued' => false,
                'certificate_issued_at' => null,
            ]);

            if ($certificateable instanceof CourseSubscription) {
                $certificateable->update(['completion_certificate_url' => null]);
            }
        }

        return true;
    }

    /**
     * Get default template text from academy settings
     */
    protected function getDefaultTemplateText(Academy $academy, CertificateType $type): string
    {
        $settings = $academy->settings ?? $academy->getOrCreateSettings();

        $key = match($type) {
            CertificateType::RECORDED_COURSE => 'certificates.templates.recorded_course',
            CertificateType::INTERACTIVE_COURSE => 'certificates.templates.interactive_course',
            CertificateType::QURAN_SUBSCRIPTION => 'certificates.templates.quran_default',
            CertificateType::ACADEMIC_SUBSCRIPTION => 'certificates.templates.academic_default',
        };

        $default = match($type) {
            CertificateType::RECORDED_COURSE => 'هذا يشهد بأن {student_name} قد أتم بنجاح دورة {course_name} بتاريخ {completion_date}.',
            CertificateType::INTERACTIVE_COURSE => 'هذا يشهد بأن {student_name} قد أتم بنجاح الدورة التفاعلية {course_name} تحت إشراف المعلم {teacher_name}.',
            CertificateType::QURAN_SUBSCRIPTION => 'هذا يشهد بأن {student_name} قد أتم {achievement} تحت إشراف المعلم {teacher_name} في أكاديمية {academy_name}.',
            CertificateType::ACADEMIC_SUBSCRIPTION => 'هذا يشهد بأن {student_name} قد أتم {achievement} تحت إشراف المعلم {teacher_name} في أكاديمية {academy_name}.',
        };

        return $settings->getSetting($key, $default);
    }

    /**
     * Replace placeholders in template text
     */
    protected function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }

        return $text;
    }
}
