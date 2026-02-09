<?php

namespace App\Contracts;

use App\Enums\CertificateTemplateStyle;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\User;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Certificate Service Interface
 *
 * Defines the contract for certificate management operations including:
 * - Certificate generation and issuance
 * - PDF generation and rendering
 * - Certificate preview and download
 * - Certificate revocation
 */
interface CertificateServiceInterface
{
    /**
     * Generate a unique certificate number
     *
     * @return string The generated certificate number
     */
    public function generateCertificateNumber(): string;

    /**
     * Issue certificate for a recorded course completion
     *
     * Validates that the student has completed 100% of the course before issuing.
     *
     * @param  CourseSubscription  $subscription  The course subscription
     * @return Certificate The issued certificate
     *
     * @throws \Exception If student has not completed 100% of the course or certificate already issued
     */
    public function issueCertificateForRecordedCourse(CourseSubscription $subscription): Certificate;

    /**
     * Issue certificate for an interactive course
     *
     * Validates that the enrollment status is 'completed' before issuing.
     *
     * @param  InteractiveCourseEnrollment  $enrollment  The course enrollment
     * @return Certificate The issued certificate
     *
     * @throws \Exception If enrollment is not completed or certificate already issued
     */
    public function issueCertificateForInteractiveCourse(InteractiveCourseEnrollment $enrollment): Certificate;

    /**
     * Issue manual certificate for Quran or Academic subscriptions
     *
     * Allows custom achievement text and template style selection.
     *
     * @param  mixed  $subscriptionable  QuranSubscription or AcademicSubscription instance
     * @param  string  $achievementText  Custom achievement text for the certificate
     * @param  CertificateTemplateStyle|string  $templateStyle  Template style enum or string value
     * @param  int|null  $issuedBy  User ID of the person issuing the certificate
     * @param  int|null  $teacherId  Teacher ID to be shown on certificate
     * @return Certificate The issued certificate
     *
     * @throws \Exception If subscription type is invalid or certificate already issued
     */
    public function issueManualCertificate(
        $subscriptionable,
        string $achievementText,
        CertificateTemplateStyle|string $templateStyle,
        ?int $issuedBy = null,
        ?int $teacherId = null
    ): Certificate;

    /**
     * Generate certificate PDF using FPDI + TCPDF
     *
     * @param  Certificate  $certificate  The certificate to generate PDF for
     * @return Fpdi The generated PDF object
     */
    public function generateCertificatePdf(Certificate $certificate): Fpdi;

    /**
     * Get certificate data for PDF generation
     *
     * Returns structured data including student info, academy info, and certificate metadata.
     *
     * @param  Certificate  $certificate  The certificate
     * @return array Certificate data array
     */
    public function getCertificateData(Certificate $certificate): array;

    /**
     * Preview certificate without saving
     *
     * Generates a PDF preview using provided data and template style.
     *
     * @param  array  $data  Certificate data for preview
     * @param  CertificateTemplateStyle|string  $templateStyle  Template style enum or string value
     * @return Fpdi The preview PDF object
     */
    public function previewCertificate(
        array $data,
        CertificateTemplateStyle|string $templateStyle
    ): Fpdi;

    /**
     * Download certificate
     *
     * Returns a download response for the certificate PDF. Regenerates PDF if file doesn't exist.
     *
     * @param  Certificate  $certificate  The certificate to download
     * @return \Symfony\Component\HttpFoundation\StreamedResponse Download response
     */
    public function downloadCertificate(Certificate $certificate);

    /**
     * Stream certificate for viewing in browser
     *
     * Returns an inline PDF response. Regenerates PDF if file doesn't exist.
     *
     * @param  Certificate  $certificate  The certificate to stream
     * @return \Illuminate\Http\Response PDF stream response
     */
    public function streamCertificate(Certificate $certificate);

    /**
     * Revoke certificate
     *
     * Soft deletes the certificate and updates the related subscription status.
     *
     * @param  Certificate  $certificate  The certificate to revoke
     * @return bool True if revocation was successful
     */
    public function revokeCertificate(Certificate $certificate): bool;

    /**
     * Issue certificate for a group circle student
     *
     * Creates certificates directly linked to QuranCircle for students in group circles
     * who don't have individual subscriptions.
     *
     * @param  QuranCircle  $circle  The Quran circle
     * @param  User  $student  The student user
     * @param  string  $achievementText  Custom achievement text
     * @param  CertificateTemplateStyle|string  $templateStyle  Template style enum or string value
     * @param  int|null  $issuedBy  User ID of the person issuing the certificate
     * @return Certificate The issued certificate
     */
    public function issueGroupCircleCertificate(
        QuranCircle $circle,
        User $student,
        string $achievementText,
        CertificateTemplateStyle|string $templateStyle,
        ?int $issuedBy = null
    ): Certificate;

    /**
     * Issue manual certificate for an interactive course student
     *
     * Allows multiple certificates with custom achievement text for interactive course students.
     *
     * @param  InteractiveCourse  $course  The interactive course
     * @param  User  $student  The student user
     * @param  string  $achievementText  Custom achievement text
     * @param  CertificateTemplateStyle|string  $templateStyle  Template style enum or string value
     * @param  int|null  $issuedBy  User ID of the person issuing the certificate
     * @return Certificate The issued certificate
     */
    public function issueInteractiveCourseCertificate(
        InteractiveCourse $course,
        User $student,
        string $achievementText,
        CertificateTemplateStyle|string $templateStyle,
        ?int $issuedBy = null
    ): Certificate;
}
