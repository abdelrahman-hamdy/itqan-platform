<?php

namespace App\Enums;

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

    public function getLabel(): string
    {
        return match($this) {
            self::SAUDI_ARABIA => 'السعودية',
            self::UAE => 'الإمارات العربية المتحدة',
            self::EGYPT => 'مصر',
            self::QATAR => 'قطر',
            self::KUWAIT => 'الكويت',
            self::BAHRAIN => 'البحرين',
            self::OMAN => 'عمان',
            self::JORDAN => 'الأردن',
            self::LEBANON => 'لبنان',
            self::IRAQ => 'العراق',
            self::SYRIA => 'سوريا',
            self::YEMEN => 'اليمن',
            self::PALESTINE => 'فلسطين',
            self::MOROCCO => 'المغرب',
            self::ALGERIA => 'الجزائر',
            self::TUNISIA => 'تونس',
            self::LIBYA => 'ليبيا',
            self::SUDAN => 'السودان',
            self::SOMALIA => 'الصومال',
            self::DJIBOUTI => 'جيبوتي',
            self::COMOROS => 'جزر القمر',
            self::MAURITANIA => 'موريتانيا',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_map(fn($case) => $case->value, self::cases()),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }

    public static function default(): self
    {
        return self::SAUDI_ARABIA;
    }
}
