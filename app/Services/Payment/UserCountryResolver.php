<?php

namespace App\Services\Payment;

use App\Helpers\CountryList;
use App\Models\Academy;
use App\Models\User;

/**
 * Resolves a user's country (ISO 3166-1 alpha-2) for payment gateway filtering.
 *
 * Resolution priority when a user is given:
 *   1. Explicit `phone_country` ISO column on the user row (most authoritative —
 *      written from the phone-input component's hidden ISO field, no inference).
 *   2. Explicit `phone_country` ISO column on the student profile.
 *   3. ISO derived from `phone_country_code` on the user row (dial-code map).
 *   4. ISO derived from `phone_country_code` on the student profile.
 *   5. Leading dial code parsed from the raw `phone` string (legacy rows).
 *   6. Student profile declared nationality (self-declared fallback —
 *      legal nationality ≠ residence, but better than nothing).
 *   7. `null` — we deliberately do NOT fall back to `$academy->country` when
 *      the user is known, because the same academy serves many countries
 *      and assuming the academy's home country leads to incorrect
 *      gateway routing (e.g. USA student auto-routed to a KSA-locked gateway).
 *
 * When no user is given (anonymous flows, e.g. landing-page gateway preview),
 * the academy country is the only signal available and is returned as-is.
 */
final class UserCountryResolver
{
    public function resolve(?User $user, ?Academy $academy = null): ?string
    {
        if ($user === null) {
            return $academy?->country?->value;
        }

        $userIso = $this->normalizeIso($user->phone_country ?? null);
        if ($userIso !== null) {
            return $userIso;
        }

        $studentProfile = $user->studentProfile;
        $profileIso = $this->normalizeIso($studentProfile?->phone_country ?? null);
        if ($profileIso !== null) {
            return $profileIso;
        }

        $iso = CountryList::dialCodeToIso($user->phone_country_code);
        if ($iso !== null) {
            return $iso;
        }

        $iso = CountryList::dialCodeToIso($studentProfile?->phone_country_code);
        if ($iso !== null) {
            return $iso;
        }

        if (! empty($user->phone)) {
            $iso = CountryList::dialCodeToIso($user->phone);
            if ($iso !== null) {
                return $iso;
            }
        }

        $nationality = $this->normalizeIso($studentProfile?->nationality);
        if ($nationality !== null) {
            return $nationality;
        }

        return null;
    }

    private function normalizeIso(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $upper = strtoupper(trim($value));
        if (! CountryList::isValid($upper)) {
            return null;
        }

        return $upper;
    }
}
