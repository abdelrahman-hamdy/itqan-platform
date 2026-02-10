<?php

namespace App\Enums;

/**
 * SessionSubscriptionStatus Enum
 *
 * Simplified status for session-based subscriptions (Quran & Academic).
 * These subscriptions are ongoing and session-based, with no completion concept.
 *
 * Lifecycle:
 * - PENDING → ACTIVE (payment received)
 * - ACTIVE → PAUSED (user/admin pauses)
 * - PAUSED → ACTIVE (resume)
 * - ACTIVE/PAUSED → CANCELLED (termination)
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
     * Check if subscription can be paused
     */
    public function canPause(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if subscription can be resumed
     */
    public function canResume(): bool
    {
        return $this === self::PAUSED;
    }

    /**
     * Check if subscription can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE, self::PAUSED]);
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAUSED]);
    }

    /**
     * Check if subscription is terminal (no further changes)
     */
    public function isTerminal(): bool
    {
        return $this === self::CANCELLED;
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
            self::ACTIVE => [self::PAUSED, self::CANCELLED],
            self::PAUSED => [self::ACTIVE, self::CANCELLED],
            self::CANCELLED => [], // Terminal
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
}
