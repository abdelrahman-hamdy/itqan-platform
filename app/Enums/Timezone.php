<?php

namespace App\Enums;

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

    public function getLabel(): string
    {
        return match($this) {
            self::RIYADH => 'الرياض (GMT+3)',
            self::DUBAI => 'دبي (GMT+4)',
            self::CAIRO => 'القاهرة (GMT+2)',
            self::QATAR => 'قطر (GMT+3)',
            self::KUWAIT => 'الكويت (GMT+3)',
            self::BAHRAIN => 'البحرين (GMT+3)',
            self::MUSCAT => 'مسقط (GMT+4)',
            self::AMMAN => 'عمّان (GMT+2)',
            self::BEIRUT => 'بيروت (GMT+2)',
            self::BAGHDAD => 'بغداد (GMT+3)',
            self::DAMASCUS => 'دمشق (GMT+2)',
            self::ADEN => 'عدن (GMT+3)',
            self::GAZA => 'غزة (GMT+2)',
            self::CASABLANCA => 'الدار البيضاء (GMT+1)',
            self::ALGIERS => 'الجزائر (GMT+1)',
            self::TUNIS => 'تونس (GMT+1)',
            self::TRIPOLI => 'طرابلس (GMT+2)',
            self::KHARTOUM => 'الخرطوم (GMT+2)',
            self::MOGADISHU => 'مقديشو (GMT+3)',
            self::DJIBOUTI => 'جيبوتي (GMT+3)',
            self::COMORO => 'القُمر (GMT+3)',
            self::NOUAKCHOTT => 'نواكشوط (GMT+0)',
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
        return self::RIYADH;
    }
}
