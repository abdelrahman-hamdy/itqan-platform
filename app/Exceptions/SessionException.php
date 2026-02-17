<?php

namespace App\Exceptions;

use Throwable;
use Exception;

/**
 * Exception for session-related errors.
 *
 * Used by SessionManagementService and related session operations.
 */
class SessionException extends Exception
{
    public const INVALID_STATUS = 'INVALID_STATUS';

    public const TIME_SLOT_UNAVAILABLE = 'TIME_SLOT_UNAVAILABLE';

    public const TEACHER_UNAVAILABLE = 'TEACHER_UNAVAILABLE';

    public const STUDENT_UNAVAILABLE = 'STUDENT_UNAVAILABLE';

    public const ALREADY_CANCELLED = 'ALREADY_CANCELLED';

    public const CANNOT_RESCHEDULE = 'CANNOT_RESCHEDULE';

    public const MEETING_ERROR = 'MEETING_ERROR';

    public const NOT_FOUND = 'NOT_FOUND';

    protected string $errorCode;

    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'SESSION_ERROR',
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function invalidStatus(string $currentStatus, string $requiredStatus): self
    {
        return new self(
            "Cannot perform this action. Current status is '{$currentStatus}', required: '{$requiredStatus}'",
            self::INVALID_STATUS,
            ['current_status' => $currentStatus, 'required_status' => $requiredStatus]
        );
    }

    public static function timeSlotUnavailable(string $dateTime, ?string $reason = null): self
    {
        $message = "Time slot is not available: {$dateTime}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(
            $message,
            self::TIME_SLOT_UNAVAILABLE,
            ['datetime' => $dateTime, 'reason' => $reason]
        );
    }

    public static function teacherUnavailable(int $teacherId, string $dateTime): self
    {
        return new self(
            "Teacher is not available at {$dateTime}",
            self::TEACHER_UNAVAILABLE,
            ['teacher_id' => $teacherId, 'datetime' => $dateTime]
        );
    }

    public static function studentUnavailable(int $studentId, string $dateTime): self
    {
        return new self(
            "Student is not available at {$dateTime}",
            self::STUDENT_UNAVAILABLE,
            ['student_id' => $studentId, 'datetime' => $dateTime]
        );
    }

    public static function alreadyCancelled(int $sessionId): self
    {
        return new self(
            'This session has already been cancelled',
            self::ALREADY_CANCELLED,
            ['session_id' => $sessionId]
        );
    }

    public static function cannotReschedule(int $sessionId, string $reason): self
    {
        return new self(
            "Cannot reschedule session: {$reason}",
            self::CANNOT_RESCHEDULE,
            ['session_id' => $sessionId, 'reason' => $reason]
        );
    }

    public static function meetingError(string $reason): self
    {
        return new self(
            "Meeting error: {$reason}",
            self::MEETING_ERROR,
            ['reason' => $reason]
        );
    }

    public static function notFound(int $id): self
    {
        return new self(
            "Session not found with ID: {$id}",
            self::NOT_FOUND,
            ['session_id' => $id]
        );
    }
}
