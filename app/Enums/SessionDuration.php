<?php

namespace App\Enums;

enum SessionDuration: int
{
    case THIRTY_MINUTES = 30;
    case FOURTY_FIVE_MINUTES = 45;
    case SIXTY_MINUTES = 60;
    case NINETY_MINUTES = 90; // NEW: Added 90 minutes option

    public function label(): string
    {
        return match ($this) {
            self::THIRTY_MINUTES => '30 دقيقة',
            self::FOURTY_FIVE_MINUTES => '45 دقيقة',
            self::SIXTY_MINUTES => 'ساعة واحدة',
            self::NINETY_MINUTES => 'ساعة ونصف',
        };
    }

    /**
     * Get the label in English
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::THIRTY_MINUTES => '30 minutes',
            self::FOURTY_FIVE_MINUTES => '45 minutes',
            self::SIXTY_MINUTES => '1 hour',
            self::NINETY_MINUTES => '1.5 hours',
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
            45 => self::FOURTY_FIVE_MINUTES,
            60 => self::SIXTY_MINUTES,
            90 => self::NINETY_MINUTES,
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
}
