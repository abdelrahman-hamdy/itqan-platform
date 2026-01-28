<?php

use App\Enums\Currency;
use App\Models\Academy;
use App\Services\AcademyContextService;

if (! function_exists('getAcademyCurrency')) {
    /**
     * Get the current academy's currency
     * Falls back to SAR (Saudi Riyal) if no academy context is available
     */
    function getAcademyCurrency(?Academy $academy = null): Currency
    {
        // If academy is provided, use its currency
        if ($academy && $academy->currency) {
            return $academy->currency instanceof Currency
                ? $academy->currency
                : Currency::tryFrom($academy->currency) ?? Currency::default();
        }

        // Try to get from authenticated user's academy
        $user = auth()->user();
        if ($user && $user->academy && $user->academy->currency) {
            $currency = $user->academy->currency;

            return $currency instanceof Currency
                ? $currency
                : Currency::tryFrom($currency) ?? Currency::default();
        }

        // Try to get from AcademyContextService
        if (class_exists(AcademyContextService::class)) {
            $currentAcademy = AcademyContextService::getCurrentAcademy();
            if ($currentAcademy && $currentAcademy->currency) {
                $currency = $currentAcademy->currency;

                return $currency instanceof Currency
                    ? $currency
                    : Currency::tryFrom($currency) ?? Currency::default();
            }
        }

        // Fallback to default currency
        return Currency::default();
    }
}

if (! function_exists('getCurrencyCode')) {
    /**
     * Get the currency code (ISO 4217)
     *
     * @param  Currency|string|null  $currency
     */
    function getCurrencyCode($currency = null, ?Academy $academy = null): string
    {
        if ($currency instanceof Currency) {
            return $currency->value;
        }

        if (is_string($currency) && ! empty($currency)) {
            return $currency;
        }

        return getAcademyCurrency($academy)->value;
    }
}

if (! function_exists('getCurrencySymbol')) {
    /**
     * Get the currency symbol for display
     *
     * @param  Currency|string|null  $currency
     */
    function getCurrencySymbol($currency = null, ?Academy $academy = null): string
    {
        $code = getCurrencyCode($currency, $academy);

        // Currency symbols mapping
        $symbols = [
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            'EGP' => 'ج.م',
            'QAR' => 'ر.ق',
            'KWD' => 'د.ك',
            'BHD' => 'د.ب',
            'OMR' => 'ر.ع',
            'JOD' => 'د.أ',
            'LBP' => 'ل.ل',
            'IQD' => 'د.ع',
            'SYP' => 'ل.س',
            'YER' => 'ر.ي',
            'MAD' => 'د.م',
            'DZD' => 'د.ج',
            'TND' => 'د.ت',
            'LYD' => 'د.ل',
            'SDG' => 'ج.س',
            'SOS' => 'ش.ص',
            'DJF' => 'ف.ج',
            'KMF' => 'ف.ق',
            'MRU' => 'أ.م',
            // Common international currencies
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        return $symbols[$code] ?? $code;
    }
}

if (! function_exists('formatPrice')) {
    /**
     * Format a price with currency
     *
     * @param  float|int|string  $amount  The amount to format
     * @param  Currency|string|null  $currency  Optional currency override
     * @param  bool  $showDecimals  Whether to show decimal places (default: true)
     * @param  bool  $symbolFirst  Whether to show symbol before amount (default: false for Arabic)
     * @return string Formatted price string
     */
    function formatPrice($amount, $currency = null, ?Academy $academy = null, bool $showDecimals = true, bool $symbolFirst = false): string
    {
        // Handle null or empty amounts
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        // Convert to float
        $amount = (float) $amount;

        // Format the number
        $formattedAmount = $showDecimals
            ? number_format($amount, 2, '.', ',')
            : number_format($amount, 0, '.', ',');

        // Get currency symbol
        $symbol = getCurrencySymbol($currency, $academy);

        // Return formatted string based on locale
        $locale = app()->getLocale();

        if ($symbolFirst || $locale === 'en') {
            return "{$symbol} {$formattedAmount}";
        }

        // Arabic: amount then symbol
        return "{$formattedAmount} {$symbol}";
    }
}

if (! function_exists('formatPriceWithCode')) {
    /**
     * Format a price with currency code (e.g., "100.00 SAR")
     *
     * @param  float|int|string  $amount  The amount to format
     * @param  Currency|string|null  $currency  Optional currency override
     * @param  bool  $showDecimals  Whether to show decimal places (default: true)
     * @return string Formatted price string with code
     */
    function formatPriceWithCode($amount, $currency = null, ?Academy $academy = null, bool $showDecimals = true): string
    {
        // Handle null or empty amounts
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        // Convert to float
        $amount = (float) $amount;

        // Format the number
        $formattedAmount = $showDecimals
            ? number_format($amount, 2, '.', ',')
            : number_format($amount, 0, '.', ',');

        // Get currency code
        $code = getCurrencyCode($currency, $academy);

        return "{$formattedAmount} {$code}";
    }
}

if (! function_exists('getCurrencyLabel')) {
    /**
     * Get the localized currency label (e.g., "ريال سعودي")
     *
     * @param  Currency|string|null  $currency
     */
    function getCurrencyLabel($currency = null, ?Academy $academy = null): string
    {
        $code = getCurrencyCode($currency, $academy);
        $currencyEnum = Currency::tryFrom($code);

        if ($currencyEnum) {
            return $currencyEnum->label();
        }

        return __('enums.currency.'.$code) ?? $code;
    }
}

if (! function_exists('getCurrencyDecimals')) {
    /**
     * Get the number of decimal places for a currency
     * Most currencies use 2, but some use 0 or 3
     *
     * @param  Currency|string|null  $currency
     */
    function getCurrencyDecimals($currency = null, ?Academy $academy = null): int
    {
        $code = getCurrencyCode($currency, $academy);

        // Currencies with 0 decimal places
        $noDecimals = ['KMF', 'DJF'];

        // Currencies with 3 decimal places
        $threeDecimals = ['KWD', 'BHD', 'OMR', 'JOD', 'TND', 'LYD', 'IQD'];

        if (in_array($code, $noDecimals)) {
            return 0;
        }

        if (in_array($code, $threeDecimals)) {
            return 3;
        }

        return 2;
    }
}

if (! function_exists('convertCurrency')) {
    /**
     * Convert amount from one currency to another (placeholder for future implementation)
     * Currently returns the same amount - actual conversion requires exchange rate API
     *
     * @param  float|int  $amount
     * @param  Currency|string  $from
     * @param  Currency|string  $to
     * @return float Converted amount
     */
    function convertCurrency($amount, $from, $to): float
    {
        // Currency conversion is not implemented - returns same amount.
        // To enable conversion, integrate with an exchange rate API (e.g., exchangerate-api.com)
        // and cache rates for performance. For now, all currencies are treated as equivalent.
        if ($from === $to) {
            return (float) $amount;
        }

        // Placeholder: Return same amount when currencies differ
        // Real implementation would fetch exchange rates and convert
        return (float) $amount;
    }
}
