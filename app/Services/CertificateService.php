<?php

namespace App\Services;

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
use App\Notifications\CertificateIssuedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;

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
            ?? $academy->settings->getSetting('certificates.default_template_style', 'template_1');

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

        // Send notification to student
        $subscription->student->notify(new CertificateIssuedNotification($certificate));

        // Also notify parents
        try {
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);
            $parentNotificationService->sendCertificateIssued($certificate);
        } catch (\Exception $e) {
            \Log::error('Failed to send parent certificate notification', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);
        }

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
            ?? $academy->settings->getSetting('certificates.default_template_style', 'template_1');

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

        // Send notification to student
        if ($enrollment->student->user) {
            $enrollment->student->user->notify(new CertificateIssuedNotification($certificate));
        }

        // Also notify parents
        try {
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);
            $parentNotificationService->sendCertificateIssued($certificate);
        } catch (\Exception $e) {
            \Log::error('Failed to send parent certificate notification', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);
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

        // Get teacher
        if (!$teacherId && $subscriptionable instanceof QuranSubscription) {
            $teacherId = $subscriptionable->quran_teacher_id;
        } elseif (!$teacherId && $subscriptionable instanceof AcademicSubscription) {
            $teacherId = $subscriptionable->teacher?->user_id;
        }

        $teacherUser = $teacherId ? User::find($teacherId) : null;

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
        $subscriptionable->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
        ]);

        // Send notification to student (wrapped in try-catch to not fail certificate issuance)
        try {
            $student->notify(new CertificateIssuedNotification($certificate));
        } catch (\Exception $e) {
            \Log::warning('Certificate notification failed (certificate still issued)', [
                'certificate_id' => $certificate->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Also notify parents
        try {
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);
            $parentNotificationService->sendCertificateIssued($certificate);
        } catch (\Exception $e) {
            \Log::error('Failed to send parent certificate notification', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);
        }

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
     * Generate certificate PDF using FPDI + TCPDF
     */
    public function generateCertificatePDF(Certificate $certificate): Fpdi
    {
        $data = $this->getCertificateData($certificate);
        $data['template_style'] = $certificate->template_style;

        // Create FPDI instance (extends TCPDF with PDF import capability)
        $pdf = $this->createFpdiInstance($data['template_style']);

        // Overlay text content at specific positions
        $this->addCertificateText($pdf, $data);

        return $pdf;
    }

    /**
     * Create FPDI instance with PDF template loaded
     */
    protected function createFpdiInstance(CertificateTemplateStyle $templateStyle): Fpdi
    {
        // Create FPDI with landscape A4, RTL for Arabic
        $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Itqan Platform');
        $pdf->SetAuthor('Itqan Academy');
        $pdf->SetTitle('Certificate');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins to 0 for full-page background
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // Load the PDF template
        $pdfTemplatePath = public_path('certificates/templates/' . $templateStyle->pdfFileName());
        if (file_exists($pdfTemplatePath)) {
            // Set source file
            $pdf->setSourceFile($pdfTemplatePath);

            // Import first page
            $templateId = $pdf->importPage(1);

            // Add a page with the template
            $pdf->AddPage('L', 'A4');

            // Use the imported page as template (full page)
            $pdf->useTemplate($templateId, 0, 0, 297, 210);
        } else {
            // Fallback: just add a blank page
            $pdf->AddPage('L', 'A4');
        }

        // Enable RTL mode for Arabic
        $pdf->setRTL(true);

        return $pdf;
    }

    /**
     * Add text content to certificate at specific coordinates
     */
    protected function addCertificateText(Fpdi $pdf, array $data): void
    {
        $templateStyle = $data['template_style'];
        $primaryColor = $this->hexToRgb($templateStyle->primaryColor());

        // Use Amiri - elegant Arabic font designed for readability
        // Certificate Title - "شهادة تقدير"
        $pdf->SetTextColor($primaryColor['r'], $primaryColor['g'], $primaryColor['b']);
        $pdf->SetFont('amirib', '', 40);
        $pdf->SetXY(0, 35);
        $pdf->Cell(297, 15, 'شهادة تقدير', 0, 1, 'C');

        // Subtitle - "تُمنح هذه الشهادة إلى"
        $pdf->SetTextColor(85, 85, 85);
        $pdf->SetFont('amiri', '', 16);
        $pdf->SetXY(0, 55);
        $pdf->Cell(297, 10, 'تُمنح هذه الشهادة إلى', 0, 1, 'C');

        // Student Name
        $pdf->SetTextColor(26, 26, 26);
        $pdf->SetFont('amirib', '', 30);
        $pdf->SetXY(0, 70);
        $pdf->Cell(297, 15, $data['student_name'], 0, 1, 'C');

        // Certificate Text (font size 18, with increased line spacing)
        $pdf->SetTextColor(68, 68, 68);
        $pdf->SetFont('amiri', '', 18);
        $pdf->setCellHeightRatio(2.0); // Double the line height
        $pdf->SetXY(40, 95);
        $pdf->MultiCell(217, 0, $data['certificate_text'], 0, 'C');
        $pdf->setCellHeightRatio(1.25); // Reset to default

        // Footer section - Three columns: Teacher, Date, Academy (no lines)
        // Adjusted positions to avoid borders (moved closer to center)
        $footerY = 155;

        // Teacher column (moved left from X=220 to X=195)
        $pdf->SetTextColor(136, 136, 136);
        $pdf->SetFont('amiri', '', 11);
        $pdf->SetXY(195, $footerY + 2);
        $pdf->Cell(70, 6, 'المعلم', 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('amirib', '', 13);
        $pdf->SetXY(195, $footerY + 10);
        $pdf->Cell(70, 6, $data['teacher_name'] ?: '—', 0, 1, 'C');

        // Date column (center - unchanged)
        $pdf->SetTextColor(136, 136, 136);
        $pdf->SetFont('amiri', '', 11);
        $pdf->SetXY(108, $footerY + 2);
        $pdf->Cell(80, 6, 'التاريخ', 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('amirib', '', 13);
        $pdf->SetXY(108, $footerY + 10);
        $pdf->Cell(80, 6, $data['issued_date_formatted'], 0, 1, 'C');

        // Academy column (moved right from X=7 to X=32)
        $pdf->SetTextColor(136, 136, 136);
        $pdf->SetFont('amiri', '', 11);
        $pdf->SetXY(32, $footerY + 2);
        $pdf->Cell(70, 6, 'الأكاديمية', 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('amirib', '', 13);
        $pdf->SetXY(32, $footerY + 10);
        $pdf->Cell(70, 6, $data['academy_name'] ?: '—', 0, 1, 'C');

        // Certificate number at bottom
        $pdf->SetTextColor(153, 153, 153);
        $pdf->SetFont('amiri', '', 10);
        $pdf->setRTL(false); // Certificate number in LTR
        $pdf->SetXY(0, 190);
        $pdf->Cell(297, 6, $data['certificate_number'], 0, 1, 'C');
        $pdf->setRTL(true); // Back to RTL
    }

    /**
     * Convert hex color to RGB array
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
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
            'academy_name' => $academy->name,
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
    protected function storeCertificatePDF(Fpdi $pdf, Certificate $certificate): string
    {
        $academy = $certificate->academy;
        $year = now()->year;
        $type = $certificate->certificate_type->value;

        // Build file path
        $fileName = "{$certificate->student_id}_{$certificate->certificate_number}.pdf";
        $directory = "tenants/{$academy->id}/certificates/{$year}/{$type}";
        $filePath = "{$directory}/{$fileName}";

        // Save PDF - TCPDF uses Output() method with 'S' for string
        Storage::put($filePath, $pdf->Output('', 'S'));

        return $filePath;
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

        $data['template_style'] = $templateStyle;

        // Create FPDI instance with PDF template
        $pdf = $this->createFpdiInstance($templateStyle);

        // Overlay text content at specific positions
        $this->addCertificateText($pdf, $data);

        return $pdf;
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(Certificate $certificate)
    {
        if (!$certificate->fileExists()) {
            // Regenerate if file doesn't exist
            $mpdf = $this->generateCertificatePDF($certificate);
            $filePath = $this->storeCertificatePDF($mpdf, $certificate);
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
            // TCPDF Output: 'I' = inline (browser), 'D' = download, 'S' = string
            return response($pdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $certificate->certificate_number . '.pdf"');
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
        // Allow multiple certificates - no restriction check

        $academy = $circle->academy;
        $teacher = $circle->teacher;

        // Use achievement text directly as certificate text (no template wrapping)
        $certificateText = $achievementText;

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
            'certificate_text' => $certificateText,
            'custom_achievement_text' => $achievementText,
            'is_manual' => true,
            'issued_by' => $issuedBy,
            'metadata' => [
                'circle_code' => $circle->circle_code ?? null,
                'circle_name' => $circle->name,
                'circle_type' => 'group',
            ],
        ]);

        // Send notification (wrapped in try-catch to not fail certificate issuance)
        try {
            $student->notify(new CertificateIssuedNotification($certificate));
        } catch (\Exception $e) {
            \Log::warning('Certificate notification failed (certificate still issued)', [
                'certificate_id' => $certificate->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }

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
        // Allow multiple certificates - no restriction check

        $academy = $course->academy;
        $teacher = $course->assignedTeacher;

        // Use achievement text directly as certificate text (no template wrapping)
        $certificateText = $achievementText;

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
            'certificate_text' => $certificateText,
            'custom_achievement_text' => $achievementText,
            'is_manual' => true,
            'issued_by' => $issuedBy,
            'metadata' => [
                'course_code' => $course->course_code ?? null,
                'course_title' => $course->title,
                'course_type' => 'interactive',
            ],
        ]);

        // Send notification (wrapped in try-catch to not fail certificate issuance)
        try {
            $student->notify(new CertificateIssuedNotification($certificate));
        } catch (\Exception $e) {
            \Log::warning('Certificate notification failed (certificate still issued)', [
                'certificate_id' => $certificate->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $certificate;
    }
}
