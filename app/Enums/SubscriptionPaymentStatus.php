<?php

namespace App\Enums;

/**
 * SubscriptionPaymentStatus Enum
 *
 * Simplified payment status for all subscription types.
 * Refunds are handled separately by the Payment model (PaymentStatus::REFUNDED).
 *
 * States:
 * - PENDING: Awaiting payment
 * - PAID: Payment received
 * - FAILED: Payment attempt failed
 */
enum SubscriptionPaymentStatus: string
{
    case PENDING = 'pending';   // Awaiting payment
    case PAID = 'paid';         // Payment received
    case FAILED = 'failed';     // Payment attempt failed

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.subscription_payment_status.'.$this->value);
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
        };
    }

    /**
     * Get the icon for the status (Heroicons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PAID => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-exclamation-triangle',
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
