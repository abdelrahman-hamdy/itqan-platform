<?php

namespace App\DTOs;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;

/**
 * Data Transfer Object for Session Operation Results
 *
 * Represents the result of session operations like creation, update,
 * cancellation, rescheduling, or status changes.
 */
class SessionOperationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $operation,
        public readonly QuranSession|AcademicSession|InteractiveCourseSession|null $session = null,
        public readonly ?string $message = null,
        public readonly ?string $errorMessage = null,
        public readonly array $errors = [],
        public readonly array $changes = [],
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a successful creation result
     */
    public static function created(
        QuranSession|AcademicSession|InteractiveCourseSession $session,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            operation: 'create',
            session: $session,
            message: $message ?? 'تم إنشاء الجلسة بنجاح',
        );
    }

    /**
     * Create a successful update result
     */
    public static function updated(
        QuranSession|AcademicSession|InteractiveCourseSession $session,
        array $changes = [],
        ?string $message = null
    ): self {
        return new self(
            success: true,
            operation: 'update',
            session: $session,
            message: $message ?? 'تم تحديث الجلسة بنجاح',
            changes: $changes,
        );
    }

    /**
     * Create a successful cancellation result
     */
    public static function cancelled(
        QuranSession|AcademicSession|InteractiveCourseSession $session,
        ?string $reason = null
    ): self {
        return new self(
            success: true,
            operation: 'cancel',
            session: $session,
            message: 'تم إلغاء الجلسة بنجاح',
            metadata: ['cancellation_reason' => $reason],
        );
    }

    /**
     * Create a successful reschedule result
     */
    public static function rescheduled(
        QuranSession|AcademicSession|InteractiveCourseSession $session,
        string $previousTime,
        string $newTime,
        ?string $reason = null
    ): self {
        return new self(
            success: true,
            operation: 'reschedule',
            session: $session,
            message: 'تم إعادة جدولة الجلسة بنجاح',
            changes: [
                'scheduled_at' => [
                    'from' => $previousTime,
                    'to' => $newTime,
                ],
            ],
            metadata: ['reschedule_reason' => $reason],
        );
    }

    /**
     * Create a successful status change result
     */
    public static function statusChanged(
        QuranSession|AcademicSession|InteractiveCourseSession $session,
        string $previousStatus,
        string $newStatus
    ): self {
        return new self(
            success: true,
            operation: 'status_change',
            session: $session,
            message: 'تم تغيير حالة الجلسة بنجاح',
            changes: [
                'status' => [
                    'from' => $previousStatus,
                    'to' => $newStatus,
                ],
            ],
        );
    }

    /**
     * Create a failure result
     */
    public static function failure(
        string $operation,
        string $errorMessage,
        array $errors = [],
        QuranSession|AcademicSession|InteractiveCourseSession|null $session = null
    ): self {
        return new self(
            success: false,
            operation: $operation,
            session: $session,
            errorMessage: $errorMessage,
            errors: $errors,
        );
    }

    /**
     * Create a validation failure result
     */
    public static function validationFailed(
        string $operation,
        array $errors
    ): self {
        return new self(
            success: false,
            operation: $operation,
            errorMessage: 'فشل التحقق من صحة البيانات',
            errors: $errors,
        );
    }

    /**
     * Check if operation had changes
     */
    public function hasChanges(): bool
    {
        return ! empty($this->changes);
    }

    /**
     * Get session ID
     */
    public function getSessionId(): ?int
    {
        return $this->session?->id;
    }

    /**
     * Get session type
     */
    public function getSessionType(): ?string
    {
        if (! $this->session) {
            return null;
        }

        return match (true) {
            $this->session instanceof QuranSession => 'quran',
            $this->session instanceof AcademicSession => 'academic',
            $this->session instanceof InteractiveCourseSession => 'interactive',
            default => 'unknown',
        };
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'operation' => $this->operation,
        ];

        if ($this->success) {
            $result['message'] = $this->message;
            $result['session_id'] = $this->getSessionId();
            $result['session_type'] = $this->getSessionType();

            if ($this->hasChanges()) {
                $result['changes'] = $this->changes;
            }
        } else {
            $result['error'] = $this->errorMessage;

            if (! empty($this->errors)) {
                $result['errors'] = $this->errors;
            }
        }

        if (! empty($this->metadata)) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }
}
