<?php

namespace App\Enums;

/**
 * Circle Status Enum
 *
 * Defines the lifecycle states for Quran circles (both group and individual):
 * - QuranCircle (group circles)
 * - QuranIndividualCircle (1-on-1 circles)
 *
 * Circles transition through states from planning to completion.
 *
 * @see \App\Models\QuranCircle
 * @see \App\Models\QuranIndividualCircle
 */
enum CircleStatus: string
{
    case PENDING = 'pending';           // Awaiting start
    case ACTIVE = 'active';             // Currently active
    case COMPLETED = 'completed';       // Successfully completed
    case SUSPENDED = 'suspended';       // Temporarily suspended
    case CANCELLED = 'cancelled';       // Cancelled permanently

    /**
     * Get the localized label for the status
     */
    public function label(): string
    {
        return __('enums.circle_status.'.$this->value);
    }

    /**
     * Get the icon for the status (Remix Icons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'ri-time-line',
            self::ACTIVE => 'ri-play-circle-line',
            self::COMPLETED => 'ri-check-double-line',
            self::SUSPENDED => 'ri-pause-circle-line',
            self::CANCELLED => 'ri-close-circle-line',
        };
    }

    /**
     * Get the Filament color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::COMPLETED => 'primary',
            self::SUSPENDED => 'info',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Get the hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::PENDING => '#f59e0b',      // amber-500
            self::ACTIVE => '#22c55e',       // green-500
            self::COMPLETED => '#3b82f6',    // blue-500
            self::SUSPENDED => '#06b6d4',    // cyan-500
            self::CANCELLED => '#ef4444',    // red-500
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
            self::COMPLETED => 'bg-blue-100 text-blue-800',
            self::SUSPENDED => 'bg-cyan-100 text-cyan-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Check if circle is currently running
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if circle is in a final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if circle can be resumed
     */
    public function canResume(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Check if circle can be suspended
     */
    public function canSuspend(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if circle can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE, self::SUSPENDED]);
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Get active circle statuses
     */
    public static function activeStatuses(): array
    {
        return [self::ACTIVE];
    }
}
