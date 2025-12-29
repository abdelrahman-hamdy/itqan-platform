<?php

namespace App\Enums;

/**
 * Week Days Enum
 *
 * Represents days of the week for scheduling purposes.
 * Used by circle schedules and recurring session configurations.
 *
 * Days follow ISO week day ordering (Sunday = first day in Middle East context).
 * Labels are provided in Arabic for display in the UI.
 *
 * @see \App\Models\QuranGroupCircleSchedule
 * @see \App\Services\QuranSessionSchedulingService
 */
enum WeekDays: string
{
    case SUNDAY = 'sunday';
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';

    /**
     * Get localized label for the weekday
     */
    public function label(): string
    {
        return __('enums.week_days.' . $this->value);
    }

    /**
     * Get all weekdays as an array for form options
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $weekday) => [$weekday->value => $weekday->label()])
            ->toArray();
    }

    /**
     * Get weekdays formatted for display
     */
    public static function getDisplayNames(array $values): string
    {
        return collect($values)
            ->map(fn ($value) => self::from($value)->label())
            ->join('، ');
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
