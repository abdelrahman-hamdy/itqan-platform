<?php

namespace App\Enums;

/**
 * SubscriptionPaymentStatus Enum
 *
 * Unified payment status for all subscription types.
 * Simplified from multiple implementations:
 * - Removed 'current' (use PAID instead)
 * - Removed 'overdue' (calculate from dates)
 * - Removed 'cancelled' (use subscription status)
 */
enum SubscriptionPaymentStatus: string
{
    case PENDING = 'pending';       // Awaiting payment
    case PAID = 'paid';             // Payment received
    case FAILED = 'failed';         // Payment attempt failed
    case REFUNDED = 'refunded';     // Payment was refunded

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.subscription_payment_status.' . $this->value);
    }

    /**
     * Get the English label for the status
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Get the icon for the status (Remix Icons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'ri-time-line',
            self::PAID => 'ri-checkbox-circle-fill',
            self::FAILED => 'ri-error-warning-line',
            self::REFUNDED => 'ri-refund-line',
        };
    }

    /**
     * Get the color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'info',
        };
    }

    /**
     * Get Tailwind color classes for badges
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::PAID => 'bg-green-100 text-green-800',
            self::FAILED => 'bg-red-100 text-red-800',
            self::REFUNDED => 'bg-blue-100 text-blue-800',
        };
    }

    /**
     * Check if payment allows subscription access
     */
    public function allowsAccess(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if payment can be retried
     */
    public function canRetry(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED]);
    }

    /**
     * Check if payment can be refunded
     */
    public function canRefund(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if this is a successful payment state
     */
    public function isSuccessful(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms (value => label)
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }
}
