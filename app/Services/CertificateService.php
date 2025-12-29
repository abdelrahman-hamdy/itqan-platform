<?php

namespace App\Services;

use App\Contracts\CertificateServiceInterface;
use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Models\AcademicSubscription;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\Certificate\CertificateEmailService;
use App\Services\Certificate\CertificatePdfGenerator;
use App\Services\Certificate\CertificateRepository;
use App\Services\Certificate\CertificateTemplateEngine;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Certificate Service - Facade Pattern
 *
 * Coordinates certificate operations across specialized components:
 * - CertificateRepository: Database operations
 * - CertificateTemplateEngine: Template selection and variable substitution
 * - CertificatePdfGenerator: PDF generation using FPDI/TCPDF
 * - CertificateEmailService: Email dispatch and notifications
 */
class CertificateService implements CertificateServiceInterface
{
    public function __construct(
        protected CertificateRepository $repository,
        protected CertificateTemplateEngine $templateEngine,
        protected CertificatePdfGenerator $pdfGenerator,
        protected CertificateEmailService $emailService,
    ) {}

    /**
     * Generate a unique certificate number
     */
    public function generateCertificateNumber(): string
    {
        return $this->repository->generateCertificateNumber();
    }

    /**
     * Issue certificate for a recorded course completion
     */
    public function issueCertificateForRecordedCourse(CourseSubscription $subscription): Certificate
    {
        // Check if certificate already issued
        if ($subscription->certificate_issued || $this->repository->existsForSubscription($subscription)) {
            return $subscription->certificate;
        }

        // Verify completion
        if ($subscription->progress_percentage < 100) {
            throw new \Exception('Student must complete 100% of the course to receive a certificate.');
        }

        $course = $subscription->recordedCourse;
        $academy = $subscription->academy;

        // Get template style
        $templateStyle = $this->templateEngine->getTemplateStyle($course, $academy);

        // Get certificate text using template engine
        $certificateText = $this->templateEngine->getRecordedCourseTemplate($academy, [
            'template_text' => $course->certificate_template_text,
            'student_name' => $subscription->student->name,
            'course_name' => $course->title,
            'completion_date' => $subscription->completion_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
        ]);

        // Create certificate record with PDF generation
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

        // Update subscription with certificate URL
        $this->repository->updateSubscriptionWithUrl($subscription, $certificate->download_url);

        // Send notifications
        $this->emailService->sendToAll($certificate);

        return $certificate;
    }

    /**
     * Issue certificate for an interactive course
     */
    public function issueCertificateForInteractiveCourse(InteractiveCourseEnrollment $enrollment): Certificate
    {
        // Check if certificate already issued
        if ($enrollment->certificate_issued || $this->repository->existsForSubscription($enrollment)) {
            return $enrollment->certificate;
        }

        // Verify completion
        if ($enrollment->enrollment_status !== 'completed') {
            throw new \Exception('Student must complete the course to receive a certificate.');
        }

        $course = $enrollment->course;
        $academy = $enrollment->academy;
        $teacher = $course->assignedTeacher;

        // Get template style
        $templateStyle = $this->templateEngine->getTemplateStyle($course, $academy);

        // Get certificate text using template engine
        $certificateText = $this->templateEngine->getInteractiveCourseTemplate($academy, [
            'student_name' => $enrollment->student->user->name ?? $enrollment->student->full_name,
            'course_name' => $course->title,
            'completion_date' => now()->format('Y-m-d'),
            'teacher_name' => $teacher->user->name ?? '',
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
        $this->repository->updateSubscriptionStatus($enrollment, true);

        // Send notifications
        if ($enrollment->student->user) {
            $this->emailService->sendToAll($certificate);
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
        if ($subscriptionable->certificate_issued || $this->repository->existsForSubscription($subscriptionable)) {
            throw new \Exception('Certificate already issued for this subscription.');
        }

        $academy = $subscriptionable->academy;
        $student = $subscriptionable->student;

        // Determine certificate type
        $certificateType = $subscriptionable instanceof QuranSubscription
            ? CertificateType::QURAN_SUBSCRIPTION
            : CertificateType::ACADEMIC_SUBSCRIPTION;

        // Get teacher
        if (!$teacherId && $subscriptionable instanceof QuranSubscription) {
            $teacherId = $subscriptionable->quran_teacher_id;
        } elseif (!$teacherId && $subscriptionable instanceof AcademicSubscription) {
            $teacherId = $subscriptionable->teacher?->user_id;
        }

        // Use achievement text directly as certificate text (no template wrapping)
        $certificateText = $achievementText;

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
        $this->repository->updateSubscriptionStatus($subscriptionable, true);

        // Send notifications (non-blocking)
        $this->emailService->sendToAll($certificate);

        return $certificate;
    }

    /**
     * Create certificate record and generate PDF
     */
    protected function createCertificate(array $data): Certificate
    {
        // Create certificate record (without file_path first)
        $data['file_path'] = 'temp'; // Temporary placeholder
        $certificate = $this->repository->create($data);

        // Generate and store PDF
        $certificateData = $this->repository->getCertificateData($certificate);
        $pdf = $this->pdfGenerator->generatePdf($certificate, $certificateData);
        $filePath = $this->pdfGenerator->storePdf($pdf, $certificate);

        // Update certificate with actual file path
        $certificate = $this->repository->update($certificate, ['file_path' => $filePath]);

        return $certificate;
    }

    /**
     * Generate certificate PDF using FPDI + TCPDF
     */
    public function generateCertificatePDF(Certificate $certificate): Fpdi
    {
        $data = $this->repository->getCertificateData($certificate);
        return $this->pdfGenerator->generatePdf($certificate, $data);
    }

    /**
     * Get certificate data for PDF generation
     */
    public function getCertificateData(Certificate $certificate): array
    {
        return $this->repository->getCertificateData($certificate);
    }

    /**
     * Preview certificate without saving
     */
    public function previewCertificate(
        array $data,
        CertificateTemplateStyle|string $templateStyle
    ): Fpdi {
        if (is_string($templateStyle)) {
            $templateStyle = CertificateTemplateStyle::from($templateStyle);
        }

        return $this->pdfGenerator->generatePreviewPdf($data, $templateStyle);
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(Certificate $certificate): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!$certificate->fileExists()) {
            // Regenerate if file doesn't exist
            $pdf = $this->generateCertificatePDF($certificate);
            $filePath = $this->pdfGenerator->storePdf($pdf, $certificate);
            $this->repository->update($certificate, ['file_path' => $filePath]);
        }

        return \Illuminate\Support\Facades\Storage::download(
            $certificate->file_path,
            "{$certificate->certificate_number}.pdf"
        );
    }

    /**
     * Stream certificate (view in browser)
     */
    public function streamCertificate(Certificate $certificate): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!$certificate->fileExists()) {
            // Regenerate if file doesn't exist
            $pdf = $this->generateCertificatePDF($certificate);
            $pdfString = $this->pdfGenerator->getPdfString($pdf);

            return response($pdfString, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $certificate->certificate_number . '.pdf"');
        }

        return response()->file(\Illuminate\Support\Facades\Storage::path($certificate->file_path));
    }

    /**
     * Revoke certificate
     */
    public function revokeCertificate(Certificate $certificate): bool
    {
        // Soft delete the certificate
        $this->repository->delete($certificate);

        // Update the related subscription
        $certificateable = $certificate->certificateable;
        if ($certificateable) {
            $this->repository->updateSubscriptionStatus($certificateable, false);
        }

        return true;
    }

    /**
     * Issue certificate for a group circle student
     * Since group circles don't have individual subscriptions, we create
     * certificates directly linked to the QuranCircle model
     */
    public function issueGroupCircleCertificate(
        \App\Models\QuranCircle $circle,
        User $student,
        string $achievementText,
        CertificateTemplateStyle|string $templateStyle,
        ?int $issuedBy = null
    ): Certificate {
        $academy = $circle->academy;

        // Convert template style to enum if string
        if (is_string($templateStyle)) {
            $templateStyle = CertificateTemplateStyle::from($templateStyle);
        }

        // Create certificate (linked to QuranCircle instead of subscription)
        $certificate = $this->createCertificate([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'teacher_id' => $circle->quran_teacher_id,
            'certificateable_type' => \App\Models\QuranCircle::class,
            'certificateable_id' => $circle->id,
            'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
            'template_style' => $templateStyle,
            'certificate_text' => $achievementText,
            'custom_achievement_text' => $achievementText,
            'is_manual' => true,
            'issued_by' => $issuedBy,
            'metadata' => [
                'circle_code' => $circle->circle_code ?? null,
                'circle_name' => $circle->name,
                'circle_type' => 'group',
            ],
        ]);

        // Send notifications (non-blocking)
        $this->emailService->sendToAll($certificate);

        return $certificate;
    }

    /**
     * Issue manual certificate for an interactive course student
     * Similar to group circle certificates, allows multiple certificates
     * with custom achievement text and template
     */
    public function issueInteractiveCourseCertificate(
        InteractiveCourse $course,
        User $student,
        string $achievementText,
        CertificateTemplateStyle|string $templateStyle,
        ?int $issuedBy = null
    ): Certificate {
        $academy = $course->academy;
        $teacher = $course->assignedTeacher;

        // Convert template style to enum if string
        if (is_string($templateStyle)) {
            $templateStyle = CertificateTemplateStyle::from($templateStyle);
        }

        // Create certificate (linked to InteractiveCourse)
        $certificate = $this->createCertificate([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher?->user_id ?? $teacher?->id,
            'certificateable_type' => InteractiveCourse::class,
            'certificateable_id' => $course->id,
            'certificate_type' => CertificateType::INTERACTIVE_COURSE,
            'template_style' => $templateStyle,
            'certificate_text' => $achievementText,
            'custom_achievement_text' => $achievementText,
            'is_manual' => true,
            'issued_by' => $issuedBy,
            'metadata' => [
                'course_code' => $course->course_code ?? null,
                'course_title' => $course->title,
                'course_type' => 'interactive',
            ],
        ]);

        // Send notifications (non-blocking)
        $this->emailService->sendToAll($certificate);

        return $certificate;
    }
}
