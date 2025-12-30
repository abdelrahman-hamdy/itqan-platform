<?php

declare(strict_types=1);

namespace App\Services\Calendar;

/**
 * Event Handler Result Value Object
 *
 * Represents the result of a calendar event operation (drag, drop, resize).
 * Used to communicate success/failure and whether the UI should revert changes.
 *
 * @see \App\Services\Calendar\CalendarEventHandler
 */
final readonly class EventHandlerResult
{
    private function __construct(
        public bool $success,
        public bool $revert,
        public string $message,
        public ?string $errorType = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(string $message): self
    {
        return new self(
            success: true,
            revert: false,
            message: $message,
            errorType: null
        );
    }

    /**
     * Create a result that indicates the UI should revert the change.
     */
    public static function revert(string $message, string $errorType = 'validation'): self
    {
        return new self(
            success: false,
            revert: true,
            message: $message,
            errorType: $errorType
        );
    }

    /**
     * Create an error result (no revert, just notify).
     */
    public static function error(string $message): self
    {
        return new self(
            success: false,
            revert: false,
            message: $message,
            errorType: 'error'
        );
    }

    /**
     * Check if this result indicates success.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the UI should revert the change.
     */
    public function shouldRevert(): bool
    {
        return $this->revert;
    }

    /**
     * Get the error type for categorized error handling.
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * Get the human-readable message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get error title based on error type (Arabic).
     */
    public function getErrorTitle(): string
    {
        return match ($this->errorType) {
            'conflict' => 'تعارض في المواعيد',
            'subscription' => 'قيود الاشتراك',
            'course' => 'قيود الدورة',
            'status' => 'حالة الجلسة',
            'past' => 'تاريخ غير صالح',
            'duration' => 'مدة غير صالحة',
            'permission' => 'غير مصرح',
            'type' => 'نوع غير صالح',
            'validation' => 'خطأ في التحقق',
            'error' => 'حدث خطأ',
            default => 'غير مسموح',
        };
    }

    /**
     * Convert to array for API responses.
     *
     * @return array{success: bool, revert: bool, message: string, errorType: string|null}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'revert' => $this->revert,
            'message' => $this->message,
            'errorType' => $this->errorType,
        ];
    }
}
