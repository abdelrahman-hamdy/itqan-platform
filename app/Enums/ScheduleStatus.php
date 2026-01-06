<?php

namespace App\Enums;

/**
 * Schedule Status Enum
 *
 * Defines the lifecycle states for session schedules.
 * Schedules are used to automatically generate recurring sessions
 * for subscriptions, circles, and courses.
 *
 * A schedule transitions through states from active to completion.
 *
 * @see \App\Models\SessionSchedule
 */
enum ScheduleStatus: string
{
    case ACTIVE = 'active';          // Schedule is active and generating sessions
    case COMPLETED = 'completed';    // Schedule has finished (reached end date or max sessions)
    case CANCELLED = 'cancelled';    // Schedule was cancelled
    case SUSPENDED = 'suspended';    // Schedule is temporarily paused

    /**
     * Get the localized label for the status
     */
    public function label(): string
    {
        return __('enums.schedule_status.'.$this->value);
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'ri-calendar-check-line',
            self::COMPLETED => 'ri-calendar-event-fill',
            self::CANCELLED => 'ri-calendar-close-line',
            self::SUSPENDED => 'ri-pause-circle-line',
        };
    }

    /**
     * Get the Filament color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::COMPLETED => 'info',
            self::CANCELLED => 'danger',
            self::SUSPENDED => 'warning',
        };
    }

    /**
     * Get the hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::ACTIVE => '#22c55e',       // green-500
            self::COMPLETED => '#3b82f6',    // blue-500
            self::CANCELLED => '#ef4444',    // red-500
            self::SUSPENDED => '#f59e0b',    // amber-500
        };
    }

    /**
     * Check if schedule can generate sessions
     */
    public function canGenerate(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if schedule is in a final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if schedule can be resumed
     */
    public function canResume(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Check if schedule can be paused
     */
    public function canPause(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if schedule can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::ACTIVE, self::SUSPENDED]);
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
     * Get color options for badge columns (color => value)
     */
    public static function colorOptions(): array
    {
        $colors = [];
        foreach (self::cases() as $status) {
            $colors[$status->color()] = $status->value;
        }

        return $colors;
    }
}
