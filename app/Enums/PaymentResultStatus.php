<?php

namespace App\Enums;

/**
 * Represents the status of a payment operation result.
 *
 * Used in PaymentResult DTO to indicate the outcome of
 * payment creation, verification, or refund operations.
 */
enum PaymentResultStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case EXPIRED = 'expired';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.payment_result_status.'.$this->value);
    }

    /**
     * Get the color class for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::SUCCESS => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
            self::PARTIALLY_REFUNDED => 'purple',
            self::EXPIRED => 'gray',
        };
    }

    /**
     * Check if this is a terminal/final status.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::SUCCESS,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::EXPIRED,
        ]);
    }

    /**
     * Check if payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if payment is still in progress.
     */
    public function isInProgress(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Get statuses that allow refund.
     */
    public static function refundableStatuses(): array
    {
        return [self::SUCCESS, self::PARTIALLY_REFUNDED];
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
