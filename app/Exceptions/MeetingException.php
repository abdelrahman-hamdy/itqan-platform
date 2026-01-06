<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for meeting-related errors (LiveKit)
 */
class MeetingException extends Exception
{
    public static function academyRequired(): self
    {
        return new self('Session must belong to an academy to create meetings');
    }

    public static function creationFailed(string $reason = ''): self
    {
        $message = 'Failed to create meeting';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    public static function roomNotCreated(): self
    {
        return new self('Meeting room not created yet');
    }

    public static function tokenGenerationFailed(string $reason = ''): self
    {
        $message = 'Failed to generate access token';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    public static function roomNotFound(string $roomName): self
    {
        return new self("Meeting room not found: {$roomName}");
    }

    public static function invalidParticipant(): self
    {
        return new self('Invalid participant for this meeting');
    }

    public static function notConfigured(): self
    {
        return new self('LiveKit service is not configured');
    }
}
