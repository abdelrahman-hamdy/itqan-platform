<?php

namespace App\Enums;

/**
 * Country Enum
 *
 * Defines supported Arab countries for user profiles and academies.
 * Uses ISO 3166-1 alpha-2 codes as values.
 *
 * @see \App\Models\User
 * @see \App\Models\Academy
 */
enum Country: string
{
    case SAUDI_ARABIA = 'SA';
    case UAE = 'AE';
    case EGYPT = 'EG';
    case QATAR = 'QA';
    case KUWAIT = 'KW';
    case BAHRAIN = 'BH';
    case OMAN = 'OM';
    case JORDAN = 'JO';
    case LEBANON = 'LB';
    case IRAQ = 'IQ';
    case SYRIA = 'SY';
    case YEMEN = 'YE';
    case PALESTINE = 'PS';
    case MOROCCO = 'MA';
    case ALGERIA = 'DZ';
    case TUNISIA = 'TN';
    case LIBYA = 'LY';
    case SUDAN = 'SD';
    case SOMALIA = 'SO';
    case DJIBOUTI = 'DJ';
    case COMOROS = 'KM';
    case MAURITANIA = 'MR';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.country.'.$this->value);
    }

    public static function toArray(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }

    public static function default(): self
    {
        return self::SAUDI_ARABIA;
    }
}
