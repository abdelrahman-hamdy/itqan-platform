<?php

namespace App\Events;

use App\Models\Certificate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a certificate is issued to a student.
 *
 * Use cases:
 * - Send notification to student
 * - Notify parent about child's achievement
 * - Update student profile achievements
 * - Log certificate issuance for analytics
 */
class CertificateIssuedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Certificate $certificate
    ) {}

    /**
     * Get the certificate model.
     */
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    /**
     * Get the student who received the certificate.
     */
    public function getStudentId(): int
    {
        return $this->certificate->student_id;
    }

    /**
     * Check if this is a manual certificate.
     */
    public function isManual(): bool
    {
        return $this->certificate->is_manual;
    }
}
