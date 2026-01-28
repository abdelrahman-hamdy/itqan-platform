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
     * Get validation rule string for nationality field.
     * Returns comma-separated ISO codes for use with 'in:' rule.
     */
    public static function validationRule(): string
    {
        return implode(',', self::getCodes());
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
