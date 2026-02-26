<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\CourseSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateRepository
{
    /**
     * Generate a unique certificate number.
     *
     * Wrapped in a DB transaction with a pessimistic lock to prevent two concurrent
     * requests from generating the same certificate number (TOCTOU race condition).
     * The DB UNIQUE constraint on certificates.certificate_number is the final safety net.
     */
    public function generateCertificateNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            do {
                $random = strtoupper(Str::random(6));
                $number = "CERT-{$year}-{$random}";
                $exists = Certificate::lockForUpdate()
                    ->where('certificate_number', $number)
                    ->exists();
            } while ($exists);

            return $number;
        });
    }

    /**
     * Create certificate record
     */
    public function create(array $data): Certificate
    {
        // Generate certificate number if not provided
        if (! isset($data['certificate_number'])) {
            $data['certificate_number'] = $this->generateCertificateNumber();
        }

        // Set issued_at if not provided
        if (! isset($data['issued_at'])) {
            $data['issued_at'] = now();
        }

        return Certificate::create($data);
    }

    /**
     * Update certificate record
     */
    public function update(Certificate $certificate, array $data): Certificate
    {
        $certificate->update($data);

        return $certificate->fresh();
    }

    /**
     * Find certificate by ID
     */
    public function find(int $id): ?Certificate
    {
        return Certificate::find($id);
    }

    /**
     * Get certificates by student, optionally scoped to a specific academy.
     *
     * Always pass $academyId when calling from a tenant context to prevent
     * a student from seeing certificates that belong to other academies.
     */
    public function getByStudent(int $studentId, ?int $academyId = null): Collection
    {
        $query = Certificate::where('student_id', $studentId);

        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        return $query->orderBy('issued_at', 'desc')->get();
    }

    /**
     * Get certificates by teacher
     */
    public function getByTeacher(int $teacherId)
    {
        return Certificate::where('teacher_id', $teacherId)
            ->orderBy('issued_at', 'desc')
            ->get();
    }

    /**
     * Get certificates by academy
     */
    public function getByAcademy(int $academyId)
    {
        return Certificate::where('academy_id', $academyId)
            ->orderBy('issued_at', 'desc')
            ->get();
    }

    /**
     * Get certificate by certificateable (polymorphic relation)
     */
    public function getByCertificateable($certificateable): ?Certificate
    {
        return Certificate::where('certificateable_type', get_class($certificateable))
            ->where('certificateable_id', $certificateable->id)
            ->first();
    }

    /**
     * Check if certificate already exists for a subscription
     */
    public function existsForSubscription($subscription): bool
    {
        return Certificate::where('certificateable_type', get_class($subscription))
            ->where('certificateable_id', $subscription->id)
            ->exists();
    }

    /**
     * Soft delete certificate
     */
    public function delete(Certificate $certificate): bool
    {
        return $certificate->delete();
    }

    /**
     * Restore soft-deleted certificate
     */
    public function restore(Certificate $certificate): bool
    {
        return $certificate->restore();
    }

    /**
     * Update subscription's certificate status
     */
    public function updateSubscriptionStatus($subscription, bool $issued): void
    {
        $updateData = [
            'certificate_issued' => $issued,
            'certificate_issued_at' => $issued ? now() : null,
        ];

        // CourseSubscription has additional field
        if ($subscription instanceof CourseSubscription && ! $issued) {
            $updateData['completion_certificate_url'] = null;
        }

        $subscription->update($updateData);
    }

    /**
     * Update subscription with certificate URL
     */
    public function updateSubscriptionWithUrl(CourseSubscription $subscription, string $url): void
    {
        $subscription->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
            'completion_certificate_url' => $url,
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
            'academy_name' => $academy->name,
            'student_name' => $student->name,
            'teacher_name' => $teacher?->name ?? '',
            'signature_name' => $signatureName,
            'signature_title' => $signatureTitle,
            'template_style' => $certificate->template_style,
            'metadata' => $certificate->metadata ?? [],
        ];
    }
}
