<?php

namespace App\Enums;

/**
 * Timezone Enum
 *
 * Defines supported timezones for Arab countries.
 * Uses PHP timezone identifiers as values.
 *
 * @see \App\Models\User
 * @see \App\Models\Academy
 */
enum Timezone: string
{
    case RIYADH = 'Asia/Riyadh';
    case DUBAI = 'Asia/Dubai';
    case CAIRO = 'Africa/Cairo';
    case QATAR = 'Asia/Qatar';
    case KUWAIT = 'Asia/Kuwait';
    case BAHRAIN = 'Asia/Bahrain';
    case MUSCAT = 'Asia/Muscat';
    case AMMAN = 'Asia/Amman';
    case BEIRUT = 'Asia/Beirut';
    case BAGHDAD = 'Asia/Baghdad';
    case DAMASCUS = 'Asia/Damascus';
    case ADEN = 'Asia/Aden';
    case GAZA = 'Asia/Gaza';
    case CASABLANCA = 'Africa/Casablanca';
    case ALGIERS = 'Africa/Algiers';
    case TUNIS = 'Africa/Tunis';
    case TRIPOLI = 'Africa/Tripoli';
    case KHARTOUM = 'Africa/Khartoum';
    case MOGADISHU = 'Africa/Mogadishu';
    case DJIBOUTI = 'Africa/Djibouti';
    case COMORO = 'Indian/Comoro';
    case NOUAKCHOTT = 'Africa/Nouakchott';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.timezone.' . $this->value);
    }

    public static function toArray(): array
    {
        return array_combine(
            array_map(fn($case) => $case->value, self::cases()),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }

    public static function default(): self
    {
        return self::RIYADH;
    }
}
