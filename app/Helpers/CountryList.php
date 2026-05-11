<?php

namespace App\Helpers;

/**
 * Unified country list - single source of truth for nationality dropdowns
 * and phone number country selectors across the entire application.
 *
 * This list MUST match the phone input countries exactly.
 * Israel is excluded to match the phone input configuration.
 *
 * @see resources/views/components/forms/phone-input.blade.php
 * @see resources/views/partials/phone-country-names.blade.php
 */
class CountryList
{
    /**
     * All supported countries keyed by ISO 3166-1 alpha-2 code.
     * Each entry: ['ar' => Arabic name, 'en' => English name]
     *
     * Ordered: Arab countries first, then alphabetical by English name.
     */
    private const COUNTRIES = [
        // === Arab Countries (prioritized) ===
        'SA' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
        'AE' => ['ar' => 'الإمارات العربية المتحدة', 'en' => 'United Arab Emirates'],
        'EG' => ['ar' => 'مصر', 'en' => 'Egypt'],
        'QA' => ['ar' => 'قطر', 'en' => 'Qatar'],
        'KW' => ['ar' => 'الكويت', 'en' => 'Kuwait'],
        'BH' => ['ar' => 'البحرين', 'en' => 'Bahrain'],
        'OM' => ['ar' => 'عُمان', 'en' => 'Oman'],
        'JO' => ['ar' => 'الأردن', 'en' => 'Jordan'],
        'LB' => ['ar' => 'لبنان', 'en' => 'Lebanon'],
        'IQ' => ['ar' => 'العراق', 'en' => 'Iraq'],
        'SY' => ['ar' => 'سوريا', 'en' => 'Syria'],
        'YE' => ['ar' => 'اليمن', 'en' => 'Yemen'],
        'PS' => ['ar' => 'فلسطين', 'en' => 'Palestine'],
        'MA' => ['ar' => 'المغرب', 'en' => 'Morocco'],
        'DZ' => ['ar' => 'الجزائر', 'en' => 'Algeria'],
        'TN' => ['ar' => 'تونس', 'en' => 'Tunisia'],
        'LY' => ['ar' => 'ليبيا', 'en' => 'Libya'],
        'SD' => ['ar' => 'السودان', 'en' => 'Sudan'],
        'SS' => ['ar' => 'جنوب السودان', 'en' => 'South Sudan'],
        'SO' => ['ar' => 'الصومال', 'en' => 'Somalia'],
        'DJ' => ['ar' => 'جيبوتي', 'en' => 'Djibouti'],
        'KM' => ['ar' => 'جزر القمر', 'en' => 'Comoros'],
        'MR' => ['ar' => 'موريتانيا', 'en' => 'Mauritania'],

        // === Europe ===
        'GB' => ['ar' => 'المملكة المتحدة', 'en' => 'United Kingdom'],
        'DE' => ['ar' => 'ألمانيا', 'en' => 'Germany'],
        'FR' => ['ar' => 'فرنسا', 'en' => 'France'],
        'IT' => ['ar' => 'إيطاليا', 'en' => 'Italy'],
        'ES' => ['ar' => 'إسبانيا', 'en' => 'Spain'],
        'PT' => ['ar' => 'البرتغال', 'en' => 'Portugal'],
        'NL' => ['ar' => 'هولندا', 'en' => 'Netherlands'],
        'BE' => ['ar' => 'بلجيكا', 'en' => 'Belgium'],
        'AT' => ['ar' => 'النمسا', 'en' => 'Austria'],
        'CH' => ['ar' => 'سويسرا', 'en' => 'Switzerland'],
        'SE' => ['ar' => 'السويد', 'en' => 'Sweden'],
        'NO' => ['ar' => 'النرويج', 'en' => 'Norway'],
        'DK' => ['ar' => 'الدنمارك', 'en' => 'Denmark'],
        'FI' => ['ar' => 'فنلندا', 'en' => 'Finland'],
        'IE' => ['ar' => 'أيرلندا', 'en' => 'Ireland'],
        'PL' => ['ar' => 'بولندا', 'en' => 'Poland'],
        'CZ' => ['ar' => 'التشيك', 'en' => 'Czech Republic'],
        'RO' => ['ar' => 'رومانيا', 'en' => 'Romania'],
        'HU' => ['ar' => 'المجر', 'en' => 'Hungary'],
        'GR' => ['ar' => 'اليونان', 'en' => 'Greece'],
        'BG' => ['ar' => 'بلغاريا', 'en' => 'Bulgaria'],
        'HR' => ['ar' => 'كرواتيا', 'en' => 'Croatia'],
        'SK' => ['ar' => 'سلوفاكيا', 'en' => 'Slovakia'],
        'SI' => ['ar' => 'سلوفينيا', 'en' => 'Slovenia'],
        'RS' => ['ar' => 'صربيا', 'en' => 'Serbia'],
        'BA' => ['ar' => 'البوسنة والهرسك', 'en' => 'Bosnia and Herzegovina'],
        'ME' => ['ar' => 'الجبل الأسود', 'en' => 'Montenegro'],
        'MK' => ['ar' => 'مقدونيا الشمالية', 'en' => 'North Macedonia'],
        'AL' => ['ar' => 'ألبانيا', 'en' => 'Albania'],
        'XK' => ['ar' => 'كوسوفو', 'en' => 'Kosovo'],
        'EE' => ['ar' => 'إستونيا', 'en' => 'Estonia'],
        'LV' => ['ar' => 'لاتفيا', 'en' => 'Latvia'],
        'LT' => ['ar' => 'ليتوانيا', 'en' => 'Lithuania'],
        'LU' => ['ar' => 'لوكسمبورغ', 'en' => 'Luxembourg'],
        'MT' => ['ar' => 'مالطا', 'en' => 'Malta'],
        'CY' => ['ar' => 'قبرص', 'en' => 'Cyprus'],
        'IS' => ['ar' => 'آيسلندا', 'en' => 'Iceland'],
        'LI' => ['ar' => 'ليختنشتاين', 'en' => 'Liechtenstein'],
        'MC' => ['ar' => 'موناكو', 'en' => 'Monaco'],
        'AD' => ['ar' => 'أندورا', 'en' => 'Andorra'],
        'SM' => ['ar' => 'سان مارينو', 'en' => 'San Marino'],
        'VA' => ['ar' => 'الفاتيكان', 'en' => 'Vatican City'],
        'MD' => ['ar' => 'مولدوفا', 'en' => 'Moldova'],
        'UA' => ['ar' => 'أوكرانيا', 'en' => 'Ukraine'],
        'BY' => ['ar' => 'بيلاروسيا', 'en' => 'Belarus'],
        'RU' => ['ar' => 'روسيا', 'en' => 'Russia'],
        'GE' => ['ar' => 'جورجيا', 'en' => 'Georgia'],
        'AM' => ['ar' => 'أرمينيا', 'en' => 'Armenia'],
        'AZ' => ['ar' => 'أذربيجان', 'en' => 'Azerbaijan'],
        'GG' => ['ar' => 'غيرنزي', 'en' => 'Guernsey'],
        'JE' => ['ar' => 'جيرزي', 'en' => 'Jersey'],
        'IM' => ['ar' => 'جزيرة مان', 'en' => 'Isle of Man'],
        'GI' => ['ar' => 'جبل طارق', 'en' => 'Gibraltar'],
        'FO' => ['ar' => 'جزر فارو', 'en' => 'Faroe Islands'],
        'AX' => ['ar' => 'جزر آلاند', 'en' => 'Åland Islands'],
        'SJ' => ['ar' => 'سفالبارد ويان ماين', 'en' => 'Svalbard and Jan Mayen'],

        // === Americas ===
        'US' => ['ar' => 'الولايات المتحدة', 'en' => 'United States'],
        'CA' => ['ar' => 'كندا', 'en' => 'Canada'],
        'MX' => ['ar' => 'المكسيك', 'en' => 'Mexico'],
        'BR' => ['ar' => 'البرازيل', 'en' => 'Brazil'],
        'AR' => ['ar' => 'الأرجنتين', 'en' => 'Argentina'],
        'CO' => ['ar' => 'كولومبيا', 'en' => 'Colombia'],
        'CL' => ['ar' => 'تشيلي', 'en' => 'Chile'],
        'PE' => ['ar' => 'بيرو', 'en' => 'Peru'],
        'VE' => ['ar' => 'فنزويلا', 'en' => 'Venezuela'],
        'EC' => ['ar' => 'الإكوادور', 'en' => 'Ecuador'],
        'BO' => ['ar' => 'بوليفيا', 'en' => 'Bolivia'],
        'PY' => ['ar' => 'باراغواي', 'en' => 'Paraguay'],
        'UY' => ['ar' => 'الأوروغواي', 'en' => 'Uruguay'],
        'GY' => ['ar' => 'غيانا', 'en' => 'Guyana'],
        'SR' => ['ar' => 'سورينام', 'en' => 'Suriname'],
        'GF' => ['ar' => 'غويانا الفرنسية', 'en' => 'French Guiana'],
        'PA' => ['ar' => 'بنما', 'en' => 'Panama'],
        'CR' => ['ar' => 'كوستاريكا', 'en' => 'Costa Rica'],
        'NI' => ['ar' => 'نيكاراغوا', 'en' => 'Nicaragua'],
        'HN' => ['ar' => 'هندوراس', 'en' => 'Honduras'],
        'SV' => ['ar' => 'السلفادور', 'en' => 'El Salvador'],
        'GT' => ['ar' => 'غواتيمالا', 'en' => 'Guatemala'],
        'BZ' => ['ar' => 'بليز', 'en' => 'Belize'],
        'CU' => ['ar' => 'كوبا', 'en' => 'Cuba'],
        'JM' => ['ar' => 'جامايكا', 'en' => 'Jamaica'],
        'HT' => ['ar' => 'هايتي', 'en' => 'Haiti'],
        'DO' => ['ar' => 'جمهورية الدومينيكان', 'en' => 'Dominican Republic'],
        'PR' => ['ar' => 'بورتوريكو', 'en' => 'Puerto Rico'],
        'TT' => ['ar' => 'ترينيداد وتوباغو', 'en' => 'Trinidad and Tobago'],
        'BB' => ['ar' => 'باربادوس', 'en' => 'Barbados'],
        'BS' => ['ar' => 'الباهاما', 'en' => 'Bahamas'],
        'AG' => ['ar' => 'أنتيغوا وباربودا', 'en' => 'Antigua and Barbuda'],
        'DM' => ['ar' => 'دومينيكا', 'en' => 'Dominica'],
        'GD' => ['ar' => 'غرينادا', 'en' => 'Grenada'],
        'KN' => ['ar' => 'سانت كيتس ونيفيس', 'en' => 'Saint Kitts and Nevis'],
        'LC' => ['ar' => 'سانت لوسيا', 'en' => 'Saint Lucia'],
        'VC' => ['ar' => 'سانت فنسنت والغرينادين', 'en' => 'Saint Vincent and the Grenadines'],
        'GP' => ['ar' => 'غوادلوب', 'en' => 'Guadeloupe'],
        'MQ' => ['ar' => 'مارتينيك', 'en' => 'Martinique'],
        'KY' => ['ar' => 'جزر كايمان', 'en' => 'Cayman Islands'],
        'BM' => ['ar' => 'برمودا', 'en' => 'Bermuda'],
        'VG' => ['ar' => 'جزر العذراء البريطانية', 'en' => 'British Virgin Islands'],
        'VI' => ['ar' => 'جزر العذراء الأمريكية', 'en' => 'U.S. Virgin Islands'],
        'AI' => ['ar' => 'أنغويلا', 'en' => 'Anguilla'],
        'MS' => ['ar' => 'مونتسرات', 'en' => 'Montserrat'],
        'TC' => ['ar' => 'جزر توركس وكايكوس', 'en' => 'Turks and Caicos Islands'],
        'CW' => ['ar' => 'كوراساو', 'en' => 'Curaçao'],
        'SX' => ['ar' => 'سينت مارتن', 'en' => 'Sint Maarten'],
        'BL' => ['ar' => 'سان بارتيلمي', 'en' => 'Saint Barthélemy'],
        'MF' => ['ar' => 'سان مارتن', 'en' => 'Saint Martin'],
        'PM' => ['ar' => 'سان بيير وميكلون', 'en' => 'Saint Pierre and Miquelon'],
        'FK' => ['ar' => 'جزر فوكلاند', 'en' => 'Falkland Islands'],
        'BQ' => ['ar' => 'بونير', 'en' => 'Caribbean Netherlands'],

        // === Asia ===
        'TR' => ['ar' => 'تركيا', 'en' => 'Turkey'],
        'IR' => ['ar' => 'إيران', 'en' => 'Iran'],
        'PK' => ['ar' => 'باكستان', 'en' => 'Pakistan'],
        'AF' => ['ar' => 'أفغانستان', 'en' => 'Afghanistan'],
        'IN' => ['ar' => 'الهند', 'en' => 'India'],
        'BD' => ['ar' => 'بنغلاديش', 'en' => 'Bangladesh'],
        'LK' => ['ar' => 'سريلانكا', 'en' => 'Sri Lanka'],
        'NP' => ['ar' => 'نيبال', 'en' => 'Nepal'],
        'BT' => ['ar' => 'بوتان', 'en' => 'Bhutan'],
        'MV' => ['ar' => 'المالديف', 'en' => 'Maldives'],
        'CN' => ['ar' => 'الصين', 'en' => 'China'],
        'JP' => ['ar' => 'اليابان', 'en' => 'Japan'],
        'KR' => ['ar' => 'كوريا الجنوبية', 'en' => 'South Korea'],
        'KP' => ['ar' => 'كوريا الشمالية', 'en' => 'North Korea'],
        'TW' => ['ar' => 'تايوان', 'en' => 'Taiwan'],
        'HK' => ['ar' => 'هونغ كونغ', 'en' => 'Hong Kong'],
        'MO' => ['ar' => 'ماكاو', 'en' => 'Macau'],
        'MN' => ['ar' => 'منغوليا', 'en' => 'Mongolia'],
        'KZ' => ['ar' => 'كازاخستان', 'en' => 'Kazakhstan'],
        'UZ' => ['ar' => 'أوزبكستان', 'en' => 'Uzbekistan'],
        'TM' => ['ar' => 'تركمانستان', 'en' => 'Turkmenistan'],
        'KG' => ['ar' => 'قيرغيزستان', 'en' => 'Kyrgyzstan'],
        'TJ' => ['ar' => 'طاجيكستان', 'en' => 'Tajikistan'],
        'MY' => ['ar' => 'ماليزيا', 'en' => 'Malaysia'],
        'ID' => ['ar' => 'إندونيسيا', 'en' => 'Indonesia'],
        'SG' => ['ar' => 'سنغافورة', 'en' => 'Singapore'],
        'PH' => ['ar' => 'الفلبين', 'en' => 'Philippines'],
        'TH' => ['ar' => 'تايلاند', 'en' => 'Thailand'],
        'VN' => ['ar' => 'فيتنام', 'en' => 'Vietnam'],
        'MM' => ['ar' => 'ميانمار', 'en' => 'Myanmar'],
        'KH' => ['ar' => 'كمبوديا', 'en' => 'Cambodia'],
        'LA' => ['ar' => 'لاوس', 'en' => 'Laos'],
        'BN' => ['ar' => 'بروناي', 'en' => 'Brunei'],
        'TL' => ['ar' => 'تيمور الشرقية', 'en' => 'Timor-Leste'],

        // === Africa ===
        'NG' => ['ar' => 'نيجيريا', 'en' => 'Nigeria'],
        'GH' => ['ar' => 'غانا', 'en' => 'Ghana'],
        'KE' => ['ar' => 'كينيا', 'en' => 'Kenya'],
        'TZ' => ['ar' => 'تنزانيا', 'en' => 'Tanzania'],
        'UG' => ['ar' => 'أوغندا', 'en' => 'Uganda'],
        'ET' => ['ar' => 'إثيوبيا', 'en' => 'Ethiopia'],
        'ER' => ['ar' => 'إريتريا', 'en' => 'Eritrea'],
        'RW' => ['ar' => 'رواندا', 'en' => 'Rwanda'],
        'BI' => ['ar' => 'بوروندي', 'en' => 'Burundi'],
        'ZA' => ['ar' => 'جنوب أفريقيا', 'en' => 'South Africa'],
        'CM' => ['ar' => 'الكاميرون', 'en' => 'Cameroon'],
        'SN' => ['ar' => 'السنغال', 'en' => 'Senegal'],
        'CI' => ['ar' => 'ساحل العاج', 'en' => 'Côte d\'Ivoire'],
        'ML' => ['ar' => 'مالي', 'en' => 'Mali'],
        'BF' => ['ar' => 'بوركينا فاسو', 'en' => 'Burkina Faso'],
        'NE' => ['ar' => 'النيجر', 'en' => 'Niger'],
        'TD' => ['ar' => 'تشاد', 'en' => 'Chad'],
        'CF' => ['ar' => 'جمهورية أفريقيا الوسطى', 'en' => 'Central African Republic'],
        'CG' => ['ar' => 'الكونغو', 'en' => 'Republic of the Congo'],
        'CD' => ['ar' => 'الكونغو الديمقراطية', 'en' => 'Democratic Republic of the Congo'],
        'GA' => ['ar' => 'الغابون', 'en' => 'Gabon'],
        'GQ' => ['ar' => 'غينيا الاستوائية', 'en' => 'Equatorial Guinea'],
        'AO' => ['ar' => 'أنغولا', 'en' => 'Angola'],
        'MZ' => ['ar' => 'موزمبيق', 'en' => 'Mozambique'],
        'MG' => ['ar' => 'مدغشقر', 'en' => 'Madagascar'],
        'MW' => ['ar' => 'ملاوي', 'en' => 'Malawi'],
        'ZM' => ['ar' => 'زامبيا', 'en' => 'Zambia'],
        'ZW' => ['ar' => 'زيمبابوي', 'en' => 'Zimbabwe'],
        'BW' => ['ar' => 'بوتسوانا', 'en' => 'Botswana'],
        'NA' => ['ar' => 'ناميبيا', 'en' => 'Namibia'],
        'SZ' => ['ar' => 'إسواتيني', 'en' => 'Eswatini'],
        'LS' => ['ar' => 'ليسوتو', 'en' => 'Lesotho'],
        'GM' => ['ar' => 'غامبيا', 'en' => 'Gambia'],
        'GN' => ['ar' => 'غينيا', 'en' => 'Guinea'],
        'GW' => ['ar' => 'غينيا بيساو', 'en' => 'Guinea-Bissau'],
        'SL' => ['ar' => 'سيراليون', 'en' => 'Sierra Leone'],
        'LR' => ['ar' => 'ليبيريا', 'en' => 'Liberia'],
        'CV' => ['ar' => 'الرأس الأخضر', 'en' => 'Cape Verde'],
        'ST' => ['ar' => 'ساو تومي وبرينسيبي', 'en' => 'São Tomé and Príncipe'],
        'TG' => ['ar' => 'توغو', 'en' => 'Togo'],
        'BJ' => ['ar' => 'بنين', 'en' => 'Benin'],
        'MU' => ['ar' => 'موريشيوس', 'en' => 'Mauritius'],
        'SC' => ['ar' => 'سيشل', 'en' => 'Seychelles'],
        'SH' => ['ar' => 'سانت هيلينا', 'en' => 'Saint Helena'],
        'YT' => ['ar' => 'مايوت', 'en' => 'Mayotte'],
        'RE' => ['ar' => 'ريونيون', 'en' => 'Réunion'],
        'EH' => ['ar' => 'الصحراء الغربية', 'en' => 'Western Sahara'],

        // === Oceania ===
        'AU' => ['ar' => 'أستراليا', 'en' => 'Australia'],
        'NZ' => ['ar' => 'نيوزيلندا', 'en' => 'New Zealand'],
        'FJ' => ['ar' => 'فيجي', 'en' => 'Fiji'],
        'PG' => ['ar' => 'بابوا غينيا الجديدة', 'en' => 'Papua New Guinea'],
        'WS' => ['ar' => 'ساموا', 'en' => 'Samoa'],
        'TO' => ['ar' => 'تونغا', 'en' => 'Tonga'],
        'VU' => ['ar' => 'فانواتو', 'en' => 'Vanuatu'],
        'SB' => ['ar' => 'جزر سليمان', 'en' => 'Solomon Islands'],
        'KI' => ['ar' => 'كيريباتي', 'en' => 'Kiribati'],
        'MH' => ['ar' => 'جزر مارشال', 'en' => 'Marshall Islands'],
        'FM' => ['ar' => 'ميكرونيزيا', 'en' => 'Micronesia'],
        'PW' => ['ar' => 'بالاو', 'en' => 'Palau'],
        'NR' => ['ar' => 'ناورو', 'en' => 'Nauru'],
        'TV' => ['ar' => 'توفالو', 'en' => 'Tuvalu'],
        'GU' => ['ar' => 'غوام', 'en' => 'Guam'],
        'AS' => ['ar' => 'ساموا الأمريكية', 'en' => 'American Samoa'],
        'MP' => ['ar' => 'جزر ماريانا الشمالية', 'en' => 'Northern Mariana Islands'],
        'PF' => ['ar' => 'بولينيزيا الفرنسية', 'en' => 'French Polynesia'],
        'NC' => ['ar' => 'كاليدونيا الجديدة', 'en' => 'New Caledonia'],
        'WF' => ['ar' => 'واليس وفوتونا', 'en' => 'Wallis and Futuna'],
        'CK' => ['ar' => 'جزر كوك', 'en' => 'Cook Islands'],
        'NU' => ['ar' => 'نيوي', 'en' => 'Niue'],
        'TK' => ['ar' => 'توكيلاو', 'en' => 'Tokelau'],
        'NF' => ['ar' => 'جزيرة نورفولك', 'en' => 'Norfolk Island'],

        // === Other Territories ===
        'GL' => ['ar' => 'غرينلاند', 'en' => 'Greenland'],
        'IO' => ['ar' => 'إقليم المحيط الهندي البريطاني', 'en' => 'British Indian Ocean Territory'],
        'CC' => ['ar' => 'جزر كوكوس', 'en' => 'Cocos (Keeling) Islands'],
        'CX' => ['ar' => 'جزيرة الكريسماس', 'en' => 'Christmas Island'],
    ];

    /**
     * Get all countries as [ISO code => localized name] for select dropdowns.
     */
    public static function toSelectArray(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $lang = str_starts_with($locale, 'ar') ? 'ar' : 'en';

        $result = [];
        foreach (self::COUNTRIES as $code => $names) {
            $result[$code] = $names[$lang];
        }

        return $result;
    }

    /**
     * Get all ISO country codes (lowercase) for phone input onlyCountries config.
     *
     * @return string[] e.g. ['sa', 'ae', 'eg', ...]
     */
    public static function getPhoneCodes(): array
    {
        return array_map('strtolower', array_keys(self::COUNTRIES));
    }

    /**
     * Get all ISO country codes (uppercase).
     *
     * @return string[] e.g. ['SA', 'AE', 'EG', ...]
     */
    public static function getCodes(): array
    {
        return array_keys(self::COUNTRIES);
    }

    /**
     * Get localized label for a given ISO code.
     */
    public static function getLabel(string $code, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $lang = str_starts_with($locale, 'ar') ? 'ar' : 'en';
        $code = strtoupper($code);

        return self::COUNTRIES[$code][$lang] ?? $code;
    }

    /**
     * Check if a given ISO code exists in the list.
     */
    public static function isValid(string $code): bool
    {
        return isset(self::COUNTRIES[strtoupper($code)]);
    }

    /**
     * Map an international dial code (e.g. "+966", "966", "00966") to an
     * ISO 3166-1 alpha-2 country code. Returns the default when no match.
     *
     * Useful for rehydrating the phone country picker on edit forms and
     * for payment gateway country resolution.
     */
    public static function dialCodeToIso(?string $dialCode, ?string $default = null): ?string
    {
        if ($dialCode === null || $dialCode === '') {
            return $default;
        }

        $digits = preg_replace('/\D+/', '', $dialCode) ?? '';
        if ($digits === '') {
            return $default;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        foreach ([3, 2, 1] as $len) {
            $prefix = substr($digits, 0, $len);
            if (isset(self::DIAL_CODE_TO_ISO[$prefix])) {
                return self::DIAL_CODE_TO_ISO[$prefix];
            }
        }

        return $default;
    }

    /**
     * ISO alpha-2 → international dial code. Single source of truth that
     * `DIAL_CODE_TO_ISO` mirrors below. NANP countries (US/CA/Caribbean/etc.)
     * all share the "1" prefix; for `isoToDialCode()` we return "1" for
     * each, but the reverse lookup picks the canonical owner ("US").
     *
     * Keep in sync with the intl-tel-input `onlyCountries` list emitted by
     * `CountryList::getPhoneCodes()` above.
     *
     * @var array<string, string>
     */
    private const ISO_TO_DIAL = [
        // === Arab Countries ===
        'SA' => '966', 'AE' => '971', 'EG' => '20', 'QA' => '974',
        'KW' => '965', 'BH' => '973', 'OM' => '968', 'JO' => '962',
        'LB' => '961', 'IQ' => '964', 'SY' => '963', 'YE' => '967',
        'PS' => '970', 'MA' => '212', 'DZ' => '213', 'TN' => '216',
        'LY' => '218', 'SD' => '249', 'SS' => '211', 'SO' => '252',
        'DJ' => '253', 'KM' => '269', 'MR' => '222',

        // === Europe ===
        'GB' => '44', 'DE' => '49', 'FR' => '33', 'IT' => '39',
        'ES' => '34', 'PT' => '351', 'NL' => '31', 'BE' => '32',
        'AT' => '43', 'CH' => '41', 'SE' => '46', 'NO' => '47',
        'DK' => '45', 'FI' => '358', 'IE' => '353', 'PL' => '48',
        'CZ' => '420', 'RO' => '40', 'HU' => '36', 'GR' => '30',
        'BG' => '359', 'HR' => '385', 'SK' => '421', 'SI' => '386',
        'RS' => '381', 'BA' => '387', 'ME' => '382', 'MK' => '389',
        'AL' => '355', 'XK' => '383', 'EE' => '372', 'LV' => '371',
        'LT' => '370', 'LU' => '352', 'MT' => '356', 'CY' => '357',
        'IS' => '354', 'LI' => '423', 'MC' => '377', 'AD' => '376',
        'SM' => '378', 'VA' => '379', 'MD' => '373', 'UA' => '380',
        'BY' => '375', 'RU' => '7', 'GE' => '995', 'AM' => '374',
        'AZ' => '994', 'GG' => '44', 'JE' => '44', 'IM' => '44',
        'GI' => '350', 'FO' => '298', 'AX' => '358', 'SJ' => '47',

        // === Americas (NANP shares +1; ISO_TO_DIAL gives the same code,
        //     reverse lookup picks the canonical owner below) ===
        'US' => '1', 'CA' => '1', 'MX' => '52', 'BR' => '55',
        'AR' => '54', 'CO' => '57', 'CL' => '56', 'PE' => '51',
        'VE' => '58', 'EC' => '593', 'BO' => '591', 'PY' => '595',
        'UY' => '598', 'GY' => '592', 'SR' => '597', 'GF' => '594',
        'PA' => '507', 'CR' => '506', 'NI' => '505', 'HN' => '504',
        'SV' => '503', 'GT' => '502', 'BZ' => '501', 'CU' => '53',
        'JM' => '1', 'HT' => '509', 'DO' => '1', 'PR' => '1',
        'TT' => '1', 'BB' => '1', 'BS' => '1', 'AG' => '1',
        'DM' => '1', 'GD' => '1', 'KN' => '1', 'LC' => '1',
        'VC' => '1', 'GP' => '590', 'MQ' => '596', 'KY' => '1',
        'BM' => '1', 'VG' => '1', 'VI' => '1', 'AI' => '1',
        'MS' => '1', 'TC' => '1', 'CW' => '599', 'SX' => '1',
        'BL' => '590', 'MF' => '590', 'PM' => '508', 'FK' => '500',
        'BQ' => '599',

        // === Asia ===
        'TR' => '90', 'IR' => '98', 'PK' => '92', 'AF' => '93',
        'IN' => '91', 'BD' => '880', 'LK' => '94', 'NP' => '977',
        'BT' => '975', 'MV' => '960', 'CN' => '86', 'JP' => '81',
        'KR' => '82', 'KP' => '850', 'TW' => '886', 'HK' => '852',
        'MO' => '853', 'MN' => '976', 'KZ' => '7', 'UZ' => '998',
        'TM' => '993', 'KG' => '996', 'TJ' => '992', 'MY' => '60',
        'ID' => '62', 'SG' => '65', 'PH' => '63', 'TH' => '66',
        'VN' => '84', 'MM' => '95', 'KH' => '855', 'LA' => '856',
        'BN' => '673', 'TL' => '670',

        // === Africa ===
        'NG' => '234', 'GH' => '233', 'KE' => '254', 'TZ' => '255',
        'UG' => '256', 'ET' => '251', 'ER' => '291', 'RW' => '250',
        'BI' => '257', 'ZA' => '27', 'CM' => '237', 'SN' => '221',
        'CI' => '225', 'ML' => '223', 'BF' => '226', 'NE' => '227',
        'TD' => '235', 'CF' => '236', 'CG' => '242', 'CD' => '243',
        'GA' => '241', 'GQ' => '240', 'AO' => '244', 'MZ' => '258',
        'MG' => '261', 'MW' => '265', 'ZM' => '260', 'ZW' => '263',
        'BW' => '267', 'NA' => '264', 'SZ' => '268', 'LS' => '266',
        'GM' => '220', 'GN' => '224', 'GW' => '245', 'SL' => '232',
        'LR' => '231', 'CV' => '238', 'ST' => '239', 'TG' => '228',
        'BJ' => '229', 'MU' => '230', 'SC' => '248', 'SH' => '290',
        'YT' => '262', 'RE' => '262', 'EH' => '212',

        // === Oceania ===
        'AU' => '61', 'NZ' => '64', 'FJ' => '679', 'PG' => '675',
        'WS' => '685', 'TO' => '676', 'VU' => '678', 'SB' => '677',
        'KI' => '686', 'MH' => '692', 'FM' => '691', 'PW' => '680',
        'NR' => '674', 'TV' => '688', 'GU' => '1', 'AS' => '1',
        'MP' => '1', 'PF' => '689', 'NC' => '687', 'WF' => '681',
        'CK' => '682', 'NU' => '683', 'TK' => '690', 'NF' => '672',

        // === Other Territories ===
        'GL' => '299', 'IO' => '246', 'CC' => '61', 'CX' => '61',
    ];

    /**
     * Dial code → ISO alpha-2. Longer prefixes are checked first by the
     * `dialCodeToIso()` lookup, so 3-digit codes always win over 2-digit
     * overlaps (e.g. "+970" Palestine beats "+97" ambiguity).
     *
     * For shared codes (the NANP "1", or "44" used by GB/GG/JE/IM, etc.)
     * we pick the most populous / canonical owner — refining attribution
     * for Caribbean islands by phone alone is not possible without the
     * NPA-NXX area-code lookup tables, which are out of scope here.
     *
     * Derived from `ISO_TO_DIAL` above; kept hand-written for readability.
     *
     * @var array<string, string>
     */
    private const DIAL_CODE_TO_ISO = [
        // === 3-digit codes (checked first) ===
        // Arab
        '966' => 'SA', '971' => 'AE', '974' => 'QA', '965' => 'KW',
        '973' => 'BH', '968' => 'OM', '962' => 'JO', '961' => 'LB',
        '964' => 'IQ', '963' => 'SY', '967' => 'YE', '970' => 'PS',
        '972' => 'PS', '212' => 'MA', '213' => 'DZ', '216' => 'TN',
        '218' => 'LY', '249' => 'SD', '211' => 'SS', '252' => 'SO',
        '253' => 'DJ', '269' => 'KM', '222' => 'MR',
        // Europe
        '351' => 'PT', '358' => 'FI', '353' => 'IE', '420' => 'CZ',
        '359' => 'BG', '385' => 'HR', '421' => 'SK', '386' => 'SI',
        '381' => 'RS', '387' => 'BA', '382' => 'ME', '389' => 'MK',
        '355' => 'AL', '383' => 'XK', '372' => 'EE', '371' => 'LV',
        '370' => 'LT', '352' => 'LU', '356' => 'MT', '357' => 'CY',
        '354' => 'IS', '423' => 'LI', '377' => 'MC', '376' => 'AD',
        '378' => 'SM', '379' => 'VA', '373' => 'MD', '380' => 'UA',
        '375' => 'BY', '995' => 'GE', '374' => 'AM', '994' => 'AZ',
        '350' => 'GI', '298' => 'FO',
        // Americas (non-NANP)
        '593' => 'EC', '591' => 'BO', '595' => 'PY', '598' => 'UY',
        '592' => 'GY', '597' => 'SR', '594' => 'GF', '507' => 'PA',
        '506' => 'CR', '505' => 'NI', '504' => 'HN', '503' => 'SV',
        '502' => 'GT', '501' => 'BZ', '509' => 'HT', '590' => 'GP',
        '596' => 'MQ', '599' => 'CW', '508' => 'PM', '500' => 'FK',
        // Asia
        '880' => 'BD', '977' => 'NP', '975' => 'BT', '960' => 'MV',
        '850' => 'KP', '886' => 'TW', '852' => 'HK', '853' => 'MO',
        '976' => 'MN', '998' => 'UZ', '993' => 'TM', '996' => 'KG',
        '992' => 'TJ', '855' => 'KH', '856' => 'LA', '673' => 'BN',
        '670' => 'TL',
        // Africa
        '234' => 'NG', '233' => 'GH', '254' => 'KE', '255' => 'TZ',
        '256' => 'UG', '251' => 'ET', '291' => 'ER', '250' => 'RW',
        '257' => 'BI', '237' => 'CM', '221' => 'SN', '225' => 'CI',
        '223' => 'ML', '226' => 'BF', '227' => 'NE', '235' => 'TD',
        '236' => 'CF', '242' => 'CG', '243' => 'CD', '241' => 'GA',
        '240' => 'GQ', '244' => 'AO', '258' => 'MZ', '261' => 'MG',
        '265' => 'MW', '260' => 'ZM', '263' => 'ZW', '267' => 'BW',
        '264' => 'NA', '268' => 'SZ', '266' => 'LS', '220' => 'GM',
        '224' => 'GN', '245' => 'GW', '232' => 'SL', '231' => 'LR',
        '238' => 'CV', '239' => 'ST', '228' => 'TG', '229' => 'BJ',
        '230' => 'MU', '248' => 'SC', '290' => 'SH', '262' => 'RE',
        // Oceania
        '679' => 'FJ', '675' => 'PG', '685' => 'WS', '676' => 'TO',
        '678' => 'VU', '677' => 'SB', '686' => 'KI', '692' => 'MH',
        '691' => 'FM', '680' => 'PW', '674' => 'NR', '688' => 'TV',
        '689' => 'PF', '687' => 'NC', '681' => 'WF', '682' => 'CK',
        '683' => 'NU', '690' => 'TK', '672' => 'NF',
        // Other Territories
        '299' => 'GL', '246' => 'IO',

        // === 2-digit codes ===
        '20' => 'EG', '90' => 'TR', '98' => 'IR', '92' => 'PK',
        '93' => 'AF', '91' => 'IN', '94' => 'LK', '86' => 'CN',
        '81' => 'JP', '82' => 'KR', '60' => 'MY', '62' => 'ID',
        '65' => 'SG', '63' => 'PH', '66' => 'TH', '84' => 'VN',
        '95' => 'MM', '27' => 'ZA', '52' => 'MX', '55' => 'BR',
        '54' => 'AR', '57' => 'CO', '56' => 'CL', '51' => 'PE',
        '58' => 'VE', '53' => 'CU', '44' => 'GB', '49' => 'DE',
        '33' => 'FR', '39' => 'IT', '34' => 'ES', '31' => 'NL',
        '32' => 'BE', '43' => 'AT', '41' => 'CH', '46' => 'SE',
        '47' => 'NO', '45' => 'DK', '48' => 'PL', '40' => 'RO',
        '36' => 'HU', '30' => 'GR', '61' => 'AU', '64' => 'NZ',
        '7' => 'RU',

        // === 1-digit codes (NANP shared; we pick US as canonical owner) ===
        '1' => 'US',
    ];

    /**
     * ISO alpha-2 country code → international dial code (digits only,
     * no leading "+"). Returns null if the code is unknown.
     *
     * Useful for seeding the phone-country picker on edit forms when only
     * the ISO is stored, and for the historical-data audit command.
     */
    public static function isoToDialCode(?string $iso): ?string
    {
        if ($iso === null || $iso === '') {
            return null;
        }

        return self::ISO_TO_DIAL[strtoupper($iso)] ?? null;
    }

    /**
     * Get the phone country names object for JavaScript (used by intl-tel-input i18n).
     * Returns [lowercase_code => localized_name].
     */
    public static function toPhoneCountryNames(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $lang = str_starts_with($locale, 'ar') ? 'ar' : 'en';

        $result = [];
        foreach (self::COUNTRIES as $code => $names) {
            $result[strtolower($code)] = $names[$lang];
        }

        return $result;
    }

    /**
     * Comma-separated ISO whitelist for the `in:` validation rule.
     * Memoized — the list is fixed for the request lifecycle.
     */
    public static function validationRule(): string
    {
        return self::$validationRuleCache ??= implode(',', array_keys(self::COUNTRIES));
    }

    private static ?string $validationRuleCache = null;

    /**
     * Validation rules for a phone-country column pair (dial + ISO).
     * Pass `required: true` for fields that must be present.
     *
     * @return array<string, string>
     */
    public static function phoneCountryRules(
        string $dialCodeField = 'phone_country_code',
        string $isoField = 'phone_country',
        bool $required = false,
    ): array {
        $prefix = $required ? 'required' : 'nullable';

        return [
            $dialCodeField => $prefix.'|string|max:5',
            $isoField => $prefix.'|string|in:'.self::validationRule(),
        ];
    }

    /**
     * Get countries as array of [value, label] objects (for API responses).
     */
    public static function toApiArray(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $lang = str_starts_with($locale, 'ar') ? 'ar' : 'en';

        return collect(self::COUNTRIES)->map(fn ($names, $code) => [
            'value' => $code,
            'label' => $names[$lang],
        ])->values()->all();
    }
}
