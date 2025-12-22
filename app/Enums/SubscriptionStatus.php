<?php

namespace App\Enums;

/**
 * SubscriptionStatus Enum
 *
 * Unified status for all subscription types:
 * - QuranSubscription
 * - AcademicSubscription
 * - CourseSubscription
 *
 * Note: No PAUSED or SUSPENDED status per user requirement (no pause feature)
 */
enum SubscriptionStatus: string
{
    case PENDING = 'pending';           // Awaiting initial payment
    case ACTIVE = 'active';             // Currently active and paid
    case PAUSED = 'paused';             // Temporarily paused by user
    case EXPIRED = 'expired';           // Time/sessions ended, not renewed
    case CANCELLED = 'cancelled';       // User or system cancelled
    case COMPLETED = 'completed';       // All sessions used successfully (Quran/Academic)
    case REFUNDED = 'refunded';         // Payment was refunded

    /**
     * Get the Arabic label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'في انتظار الدفع',
            self::ACTIVE => 'نشط',
            self::PAUSED => 'موقوف مؤقتاً',
            self::EXPIRED => 'منتهي',
            self::CANCELLED => 'ملغي',
            self::COMPLETED => 'مكتمل',
            self::REFUNDED => 'مسترد',
        };
    }

    /**
     * Get the English label for the status
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Payment',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
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
            self::ACTIVE => 'ri-checkbox-circle-line',
            self::PAUSED => 'ri-pause-circle-line',
            self::EXPIRED => 'ri-close-circle-line',
            self::CANCELLED => 'ri-forbid-line',
            self::COMPLETED => 'ri-check-double-line',
            self::REFUNDED => 'ri-refund-2-line',
        };
    }

    /**
     * Get the color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::PAUSED => 'info',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'secondary',
            self::COMPLETED => 'primary',
            self::REFUNDED => 'danger',
        };
    }

    /**
     * Get Tailwind color classes for badges
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::PAUSED => 'bg-blue-100 text-blue-800',
            self::EXPIRED => 'bg-red-100 text-red-800',
            self::CANCELLED => 'bg-gray-100 text-gray-800',
            self::COMPLETED => 'bg-purple-100 text-purple-800',
            self::REFUNDED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Check if subscription can be accessed (content viewable)
     */
    public function canAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(): bool
    {
        return in_array($this, [self::ACTIVE, self::EXPIRED]);
    }

    /**
     * Check if subscription can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE]);
    }

    /**
     * Check if subscription is terminal (no further changes)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::CANCELLED, self::COMPLETED, self::REFUNDED]);
    }

    /**
     * Check if subscription counts sessions
     */
    public function countsUsage(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get valid next statuses from current status
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::PAUSED, self::EXPIRED, self::CANCELLED, self::COMPLETED],
            self::PAUSED => [self::ACTIVE, self::CANCELLED], // Can resume or cancel
            self::EXPIRED => [self::ACTIVE, self::CANCELLED], // Can reactivate via renewal
            self::CANCELLED => [self::REFUNDED], // Can only be refunded
            self::COMPLETED => [], // Terminal
            self::REFUNDED => [], // Terminal
        };
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

    /**
     * Get active subscription statuses
     */
    public static function activeStatuses(): array
    {
        return [self::ACTIVE];
    }

    /**
     * Get renewable subscription statuses
     */
    public static function renewableStatuses(): array
    {
        return [self::ACTIVE, self::EXPIRED];
    }
}
