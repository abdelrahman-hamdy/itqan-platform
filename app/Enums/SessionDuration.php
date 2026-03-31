<?php

namespace App\Enums;

/**
 * Session Duration Enum
 *
 * Defines the available session lengths for scheduling.
 * Used by subscription packages and session creation forms.
 *
 * Available durations:
 * - FIFTEEN_MINUTES: 15-minute sessions
 * - THIRTY_MINUTES: 30-minute sessions
 * - FORTY_FIVE_MINUTES: 45-minute sessions
 * - SIXTY_MINUTES: 60-minute (1 hour) sessions
 * - NINETY_MINUTES: 90-minute (1.5 hours) sessions
 *
 * The integer value represents the duration in minutes.
 *
 * @see \App\Models\QuranSubscription
 * @see \App\Models\AcademicSubscription
 * @see \App\Models\InteractiveCourse
 */
enum SessionDuration: int
{
    case FIFTEEN_MINUTES = 15;
    case THIRTY_MINUTES = 30;
    case FORTY_FIVE_MINUTES = 45;
    case SIXTY_MINUTES = 60;
    case NINETY_MINUTES = 90;

    public function label(): string
    {
        $key = match ($this) {
            self::FIFTEEN_MINUTES => 'fifteen_minutes',
            self::THIRTY_MINUTES => 'thirty_minutes',
            self::FORTY_FIVE_MINUTES => 'forty_five_minutes',
            self::SIXTY_MINUTES => 'sixty_minutes',
            self::NINETY_MINUTES => 'ninety_minutes',
        };

        return __('enums.session_duration.'.$key);
    }

    /**
     * Get the label in English
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::FIFTEEN_MINUTES => '15 minutes',
            self::THIRTY_MINUTES => '30 minutes',
            self::FORTY_FIVE_MINUTES => '45 minutes',
            self::SIXTY_MINUTES => '1 hour',
            self::NINETY_MINUTES => '1.5 hours',
        };
    }

    /**
     * Get all duration options
     */
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

        return match ($minutes) {
            15 => self::FIFTEEN_MINUTES,
            30 => self::THIRTY_MINUTES,
            45 => self::FORTY_FIVE_MINUTES,
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

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Generate Filament TextInput fields for duration-based pricing.
     * Used by teacher profile resources and academy settings.
     *
     * @param  string  $fieldPrefix  e.g. 'individual_session_prices' or 'quran_settings.default_group_session_prices'
     * @param  string|null  $placeholder  e.g. __('filament.academy_default')
     * @param  string|null  $currencySymbol  Currency prefix. Defaults to teacher earnings currency.
     */
    public static function priceInputGrid(string $fieldPrefix, ?string $placeholder = null, ?string $currencySymbol = null): array
    {
        $symbol = $currencySymbol ?? getTeacherEarningsCurrencySymbol();

        return collect(self::cases())->map(fn (self $duration) => \Filament\Forms\Components\TextInput::make("{$fieldPrefix}.{$duration->value}")
            ->label($duration->label())
            ->numeric()
            ->prefix($symbol)
            ->minValue(0)
            ->when($placeholder, fn ($input) => $input->placeholder($placeholder))
        )->toArray();
    }
}
