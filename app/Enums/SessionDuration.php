<?php

namespace App\Enums;

enum SessionDuration: int
{
    case THIRTY_MINUTES = 30;
    case FOURTY_FIVE_MINUTES = 45;
    case SIXTY_MINUTES = 60;

    public function label(): string
    {
        return match ($this) {
            self::THIRTY_MINUTES => '30 دقيقة',
            self::FOURTY_FIVE_MINUTES => '45 دقيقة',
            self::SIXTY_MINUTES => '60 دقيقة',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
