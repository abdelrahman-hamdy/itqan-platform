<?php

namespace App\Services\Payment;

use App\Models\Academy;
use App\Models\User;

/**
 * Resolves a user's country (ISO 3166-1 alpha-2) for payment gateway filtering.
 *
 * Resolution priority:
 *   1. User phone number country (most reliable — verifiable via SMS OTP and
 *      tied to a specific telecom operator in a specific country)
 *   2. Student profile declared nationality (self-declared fallback)
 *   3. Academy country (ultimate default)
 */
final class UserCountryResolver
{
    /**
     * Map of international calling code → ISO alpha-2 country code.
     *
     * Order matters: longer prefixes must come before shorter overlapping ones
     * (e.g. "970" before "97" so Palestine doesn't get matched as UAE/Qatar).
     * Covers every country in App\Enums\Country.
     *
     * @var array<string, string>
     */
    private const CALLING_CODE_MAP = [
        // 3-digit
        '966' => 'SA', // Saudi Arabia
        '971' => 'AE', // UAE
        '974' => 'QA', // Qatar
        '965' => 'KW', // Kuwait
        '973' => 'BH', // Bahrain
        '968' => 'OM', // Oman
        '962' => 'JO', // Jordan
        '961' => 'LB', // Lebanon
        '964' => 'IQ', // Iraq
        '963' => 'SY', // Syria
        '967' => 'YE', // Yemen
        '970' => 'PS', // Palestine
        '972' => 'PS', // Palestine (alternate, Israeli-issued lines)
        '212' => 'MA', // Morocco
        '213' => 'DZ', // Algeria
        '216' => 'TN', // Tunisia
        '218' => 'LY', // Libya
        '249' => 'SD', // Sudan
        '252' => 'SO', // Somalia
        '253' => 'DJ', // Djibouti
        '269' => 'KM', // Comoros
        '222' => 'MR', // Mauritania
        // 2-digit
        '20' => 'EG',  // Egypt
    ];

    public function resolve(?User $user, ?Academy $academy = null): ?string
    {
        if ($user === null) {
            return $academy?->country?->value;
        }

        if (! empty($user->phone)) {
            $code = $this->countryFromPhone($user->phone);
            if ($code !== null) {
                return $code;
            }
        }

        $nationality = $user->studentProfile?->nationality;
        if (! empty($nationality)) {
            return strtoupper($nationality);
        }

        return $academy?->country?->value;
    }

    /**
     * Extract ISO alpha-2 country code from a phone number string.
     *
     * Accepts forms like "+966555...", "00966555...", "966555...", and returns
     * null when no known calling code prefix matches.
     */
    private function countryFromPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Try longest prefix first.
        foreach ([3, 2] as $len) {
            $prefix = substr($digits, 0, $len);
            if (isset(self::CALLING_CODE_MAP[$prefix])) {
                return self::CALLING_CODE_MAP[$prefix];
            }
        }

        return null;
    }
}
