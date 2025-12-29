<?php

namespace App\Enums;

/**
 * Session Duration Enum
 *
 * Defines the available session lengths for scheduling.
 * Used by subscription packages and session creation forms.
 *
 * Available durations:
 * - THIRTY_MINUTES: 30-minute sessions
 * - FORTY_FIVE_MINUTES: 45-minute sessions
 * - SIXTY_MINUTES: 60-minute (1 hour) sessions
 *
 * The integer value represents the duration in minutes.
 *
 * @see \App\Models\QuranSubscription
 * @see \App\Models\AcademicSubscription
 */
enum SessionDuration: int
{
    case THIRTY_MINUTES = 30;
    case FORTY_FIVE_MINUTES = 45;
    case SIXTY_MINUTES = 60;

    public function label(): string
    {
        $key = match ($this) {
            self::THIRTY_MINUTES => 'thirty_minutes',
            self::FORTY_FIVE_MINUTES => 'forty_five_minutes',
            self::SIXTY_MINUTES => 'sixty_minutes',
        };

        return __('enums.session_duration.' . $key);
    }

    /**
     * Get the label in English
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::THIRTY_MINUTES => '30 minutes',
            self::FORTY_FIVE_MINUTES => '45 minutes',
            self::SIXTY_MINUTES => '1 hour',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Get all duration options in English
     */
    public static function optionsEn(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->labelEn()])
            ->toArray();
    }

    /**
     * Create from minutes value
     */
    public static function fromMinutes(?int $minutes): ?self
    {
        if ($minutes === null) {
            return null;
        }

        return match($minutes) {
            30 => self::THIRTY_MINUTES,
            45 => self::FORTY_FIVE_MINUTES,
            60 => self::SIXTY_MINUTES,
            default => null,
        };
    }

    /**
     * Get duration in hours as a decimal
     */
    public function toHours(): float
    {
        return $this->value / 60;
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
