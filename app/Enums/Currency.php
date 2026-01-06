<?php

namespace App\Enums;

/**
 * Currency Enum
 *
 * Defines supported currencies for payments and pricing.
 * Uses ISO 4217 currency codes as values.
 *
 * @see \App\Models\Payment
 * @see \App\Models\Academy
 */
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

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.currency.'.$this->value);
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
        return self::SAR;
    }
}
