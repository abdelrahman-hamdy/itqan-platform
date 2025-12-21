<?php

namespace App\Exceptions;

use App\Enums\SessionStatus;
use Exception;

/**
 * Exception for session-related errors
 */
class SessionException extends Exception
{
    public static function invalidStatusTransition(SessionStatus $from, SessionStatus $to): self
    {
        return new self("Cannot transition from {$from->value} to {$to->value}");
    }

    public static function notScheduled(): self
    {
        return new self('Session is not scheduled');
    }

    public static function alreadyCompleted(): self
    {
        return new self('Session has already been completed');
    }

    public static function alreadyCancelled(): self
    {
        return new self('Session has already been cancelled');
    }

    public static function notFound(string|int $id): self
    {
        return new self("Session {$id} not found");
    }

    public static function cannotJoinYet(): self
    {
        return new self('Session is not yet available for joining');
    }

    public static function sessionEnded(): self
    {
        return new self('Session has ended');
    }

    public static function attendanceNotAllowed(): self
    {
        return new self('Attendance marking is not allowed for this session');
    }

    public static function feedbackAlreadySubmitted(): self
    {
        return new self('Feedback has already been submitted for this session');
    }

    public static function noTeacherAssigned(): self
    {
        return new self('No teacher assigned to this session');
    }

    public static function schedulingConflict(): self
    {
        return new self('Session scheduling conflict detected');
    }
}
