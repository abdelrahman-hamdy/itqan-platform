<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for recording-related errors
 */
class RecordingException extends Exception
{
    public static function notRecordable(): self
    {
        return new self('This session cannot be recorded at this time');
    }

    public static function startFailed(string $reason = ''): self
    {
        $message = 'Failed to start recording';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    public static function stopFailed(string $reason = ''): self
    {
        $message = 'Failed to stop recording';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    public static function notActive(): self
    {
        return new self('No active recording found');
    }

    public static function alreadyRecording(): self
    {
        return new self('Recording is already in progress');
    }

    public static function processingFailed(string $reason = ''): self
    {
        $message = 'Failed to process recording';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    public static function storageError(): self
    {
        return new self('Failed to store recording file');
    }
}
