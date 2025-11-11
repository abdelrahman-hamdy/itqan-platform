<?php

namespace App\Enums;

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
     * Get the Arabic label for the weekday
     */
    public function label(): string
    {
        return match ($this) {
            self::SUNDAY => 'الأحد',
            self::MONDAY => 'الاثنين',
            self::TUESDAY => 'الثلاثاء',
            self::WEDNESDAY => 'الأربعاء',
            self::THURSDAY => 'الخميس',
            self::FRIDAY => 'الجمعة',
            self::SATURDAY => 'السبت',
        };
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
}
