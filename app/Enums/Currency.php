<?php

namespace App\Enums;

enum Currency: string
{
    case SAR = 'SAR'; // Saudi Riyal
    case AED = 'AED'; // UAE Dirham
    case EGP = 'EGP'; // Egyptian Pound
    case QAR = 'QAR'; // Qatari Riyal
    case KWD = 'KWD'; // Kuwaiti Dinar
    case BHD = 'BHD'; // Bahraini Dinar
    case OMR = 'OMR'; // Omani Rial
    case JOD = 'JOD'; // Jordanian Dinar
    case LBP = 'LBP'; // Lebanese Pound
    case IQD = 'IQD'; // Iraqi Dinar
    case SYP = 'SYP'; // Syrian Pound
    case YER = 'YER'; // Yemeni Rial
    case ILS = 'ILS'; // Israeli Shekel
    case MAD = 'MAD'; // Moroccan Dirham
    case DZD = 'DZD'; // Algerian Dinar
    case TND = 'TND'; // Tunisian Dinar
    case LYD = 'LYD'; // Libyan Dinar
    case SDG = 'SDG'; // Sudanese Pound
    case SOS = 'SOS'; // Somali Shilling
    case DJF = 'DJF'; // Djiboutian Franc
    case KMF = 'KMF'; // Comorian Franc
    case MRU = 'MRU'; // Mauritanian Ouguiya

    public function getLabel(): string
    {
        return match($this) {
            self::SAR => 'ريال سعودي (SAR)',
            self::AED => 'درهم إماراتي (AED)',
            self::EGP => 'جنيه مصري (EGP)',
            self::QAR => 'ريال قطري (QAR)',
            self::KWD => 'دينار كويتي (KWD)',
            self::BHD => 'دينار بحريني (BHD)',
            self::OMR => 'ريال عماني (OMR)',
            self::JOD => 'دينار أردني (JOD)',
            self::LBP => 'ليرة لبنانية (LBP)',
            self::IQD => 'دينار عراقي (IQD)',
            self::SYP => 'ليرة سورية (SYP)',
            self::YER => 'ريال يمني (YER)',
            self::ILS => 'شيكل إسرائيلي (ILS)',
            self::MAD => 'درهم مغربي (MAD)',
            self::DZD => 'دينار جزائري (DZD)',
            self::TND => 'دينار تونسي (TND)',
            self::LYD => 'دينار ليبي (LYD)',
            self::SDG => 'جنيه سوداني (SDG)',
            self::SOS => 'شلن صومالي (SOS)',
            self::DJF => 'فرنك جيبوتي (DJF)',
            self::KMF => 'فرنك قُمري (KMF)',
            self::MRU => 'أوقية موريتانية (MRU)',
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
        return self::SAR;
    }
}
