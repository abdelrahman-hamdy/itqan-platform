<?php

namespace App\Enums;

/**
 * Time Slot Enum
 *
 * Defines preferred time slots for scheduling sessions.
 * Used in subscription forms for schedule preferences.
 *
 * @see \App\Models\QuranSubscription
 * @see \App\Models\AcademicSubscription
 */
enum TimeSlot: string
{
    case MORNING = 'morning';      // صباحاً (6:00 - 12:00)
    case AFTERNOON = 'afternoon';  // بعد الظهر (12:00 - 18:00)
    case EVENING = 'evening';      // مساءً (18:00 - 22:00)

    /**
     * Get localized label for the time slot
     */
    public function label(): string
    {
        return __('enums.time_slot.' . $this->value);
    }

    /**
     * Get the time range for this slot
     */
    public function timeRange(): string
    {
        return match ($this) {
            self::MORNING => '6:00 - 12:00',
            self::AFTERNOON => '12:00 - 18:00',
            self::EVENING => '18:00 - 22:00',
        };
    }

    /**
     * Get all time slot values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get time slot options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($slot) => $slot->label(), self::cases())
        );
    }
}
