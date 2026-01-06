<?php

namespace App\Enums;

/**
 * Payment Status Enum
 *
 * Tracks the lifecycle of payment transactions.
 * Used by Payment model and payment gateway integrations (Paymob, Tap).
 *
 * States:
 * - PENDING: Payment initiated, awaiting processing
 * - PROCESSING: Payment being processed by gateway
 * - COMPLETED: Payment successful
 * - FAILED: Payment failed at gateway
 * - CANCELLED: Payment cancelled by user/system
 * - REFUNDED: Full refund issued
 * - PARTIALLY_REFUNDED: Partial refund issued
 *
 * @see \App\Models\Payment
 * @see \App\Services\PaymentService
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
            self::PARTIALLY_REFUNDED => 'purple',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PROCESSING => 'heroicon-o-arrow-path',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
            self::CANCELLED => 'heroicon-o-x-mark',
            self::REFUNDED => 'heroicon-o-arrow-uturn-left',
            self::PARTIALLY_REFUNDED => 'heroicon-o-arrow-uturn-left',
        };
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if payment is in terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::PARTIALLY_REFUNDED,
        ]);
    }

    /**
     * Get all statuses as options for select inputs
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $status) => [$status->value => $status->label()]
        )->all();
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
