<?php

namespace App\Helpers;

/**
 * Resolves an ISO 3166-1 alpha-2 country code from a raw phone string ONLY
 * when the phone carries an EXPLICIT international prefix (`+` or leading
 * `00`). Bare local numbers (e.g. `509150788`) return `null` — they are
 * almost certainly Saudi mobile, not Haiti (+509), so we refuse to guess.
 *
 * Single source of truth shared by:
 *   - App\Services\Payment\UserCountryResolver
 *   - App\Console\Commands\BackfillPhoneCountryFromPrefixCommand
 *   - App\Console\Commands\BackfillPhoneCountryFromNationalityCommand
 *   - App\Console\Commands\AuditPhoneDataCommand
 */
final class PhonePrefixResolver
{
    public static function isoFromExplicitPrefix(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $trimmed = ltrim($phone);
        if (! str_starts_with($trimmed, '+') && ! str_starts_with($trimmed, '00')) {
            return null;
        }

        return CountryList::dialCodeToIso($trimmed);
    }
}
