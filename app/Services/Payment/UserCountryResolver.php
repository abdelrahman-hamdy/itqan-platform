<?php

namespace App\Services\Payment;

use App\Helpers\CountryList;
use App\Models\Academy;
use App\Models\User;

/**
 * Resolves a user's country (ISO 3166-1 alpha-2) for payment gateway filtering.
 *
 * Resolution priority when a user is given:
 *   1. Explicit `phone_country` ISO column (user, then student profile).
 *   2. ISO derived from a raw `phone` string that carries an EXPLICIT
 *      international prefix (`+` or leading `00`). Critical: the historical
 *      `phone_country_code='+966'` NOT NULL DEFAULT silently mis-labelled
 *      ~930 of our 931 users as Saudi, so we cannot trust that column when
 *      the phone itself indicates otherwise. A bare `509150788` is a Saudi
 *      mobile, NOT Haiti (+509); the prefix gate guards against that.
 *   3. `phone_country_code` (user, then student profile) via dial-code map.
 *   4. Raw `phone` without an explicit prefix — last-chance fallback.
 *   5. Student profile declared nationality.
 *   6. `null` — never fall back to `$academy->country` when a user is given.
 *
 * When no user is given (anonymous flows), the academy country is returned.
 */
final class UserCountryResolver
{
    public function resolve(?User $user, ?Academy $academy = null): ?string
    {
        if ($user === null) {
            return $academy?->country?->value;
        }

        if ($iso = $this->normalizeIso($user->phone_country ?? null)) {
            return $iso;
        }

        $studentProfile = $user->studentProfile;
        if ($iso = $this->normalizeIso($studentProfile?->phone_country ?? null)) {
            return $iso;
        }

        if ($iso = $this->isoFromExplicitPhonePrefix($user->phone)) {
            return $iso;
        }

        if ($iso = $this->isoFromExplicitPhonePrefix($studentProfile?->phone)) {
            return $iso;
        }

        if ($iso = CountryList::dialCodeToIso($user->phone_country_code)) {
            return $iso;
        }

        if ($iso = CountryList::dialCodeToIso($studentProfile?->phone_country_code)) {
            return $iso;
        }

        if (! empty($user->phone)) {
            if ($iso = CountryList::dialCodeToIso($user->phone)) {
                return $iso;
            }
        }

        return $this->normalizeIso($studentProfile?->nationality);
    }

    /**
     * Resolve an ISO from a phone only when it has an explicit `+` or `00`
     * international prefix. Returns null otherwise so that bare local-format
     * numbers fall through to the dial-code column instead of being guessed.
     */
    private function isoFromExplicitPhonePrefix(?string $phone): ?string
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

    private function normalizeIso(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $upper = strtoupper(trim($value));

        return CountryList::isValid($upper) ? $upper : null;
    }
}
