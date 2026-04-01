<?php

namespace App\Enums;

/**
 * SessionSubscriptionStatus Enum
 *
 * Simplified status for session-based subscriptions (Quran & Academic).
 *
 * Lifecycle:
 * - PENDING → ACTIVE (payment received)
 * - ACTIVE → PAUSED (user/admin pauses)
 * - PAUSED → ACTIVE (resume)
 * - ACTIVE/PAUSED → CANCELLED (termination)
 * - CANCELLED → ACTIVE (admin reactivation)
 * - ACTIVE → EXPIRED (subscription period ended)
 * - EXPIRED → ACTIVE (admin reactivation)
 *
 * @see \App\Models\QuranSubscription
 * @see \App\Models\AcademicSubscription
 */
enum SessionSubscriptionStatus: string
{
    case PENDING = 'pending';       // Awaiting payment
    case ACTIVE = 'active';         // Paid and active
    case PAUSED = 'paused';         // Temporarily stopped by user/admin
    case CANCELLED = 'cancelled';   // Terminated
    case EXPIRED = 'expired';       // Subscription period ended

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.session_subscription_status.'.$this->value);
    }

    /**
     * Get English label
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Get Filament color
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::PAUSED => 'info',
            self::CANCELLED => 'danger',
            self::EXPIRED => 'gray',
        };
    }

    /**
     * Get icon (Heroicons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::ACTIVE => 'heroicon-o-check-circle',
            self::PAUSED => 'heroicon-o-pause-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::EXPIRED => 'heroicon-o-clock',
        };
    }

    /**
     * Get Tailwind badge classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::PAUSED => 'bg-blue-100 text-blue-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
            self::EXPIRED => 'bg-gray-100 text-gray-800',
        };
    }

    public function canAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canPause(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canResume(): bool
    {
        return $this === self::PAUSED;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE, self::PAUSED, self::EXPIRED]);
    }

    public function canRenew(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAUSED, self::EXPIRED]);
    }

    public function isTerminal(): bool
    {
        return $this === self::CANCELLED;
    }

    public function canReactivate(): bool
    {
        return in_array($this, [self::CANCELLED, self::EXPIRED]);
    }

    public function countsUsage(): bool
    {
        return $this === self::ACTIVE;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::PAUSED, self::CANCELLED, self::EXPIRED],
            self::PAUSED => [self::ACTIVE, self::CANCELLED],
            self::CANCELLED => [self::ACTIVE],
            self::EXPIRED => [self::ACTIVE, self::CANCELLED],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    public static function activeStatuses(): array
    {
        return [self::ACTIVE];
    }
}
