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
    case EXPIRED = 'expired';           // Time/sessions ended, not renewed
    case CANCELLED = 'cancelled';       // User or system cancelled
    case COMPLETED = 'completed';       // All sessions used successfully (Quran/Academic)

    /**
     * Get the Arabic label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'في انتظار الدفع',
            self::ACTIVE => 'نشط',
            self::EXPIRED => 'منتهي',
            self::CANCELLED => 'ملغي',
            self::COMPLETED => 'مكتمل',
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
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
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
            self::EXPIRED => 'ri-close-circle-line',
            self::CANCELLED => 'ri-forbid-line',
            self::COMPLETED => 'ri-check-double-line',
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
            self::EXPIRED => 'danger',
            self::CANCELLED => 'secondary',
            self::COMPLETED => 'primary',
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
            self::EXPIRED => 'bg-red-100 text-red-800',
            self::CANCELLED => 'bg-gray-100 text-gray-800',
            self::COMPLETED => 'bg-blue-100 text-blue-800',
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
        return in_array($this, [self::CANCELLED, self::COMPLETED]);
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
            self::ACTIVE => [self::EXPIRED, self::CANCELLED, self::COMPLETED],
            self::EXPIRED => [self::ACTIVE, self::CANCELLED], // Can reactivate via renewal
            self::CANCELLED => [], // Terminal
            self::COMPLETED => [], // Terminal
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
