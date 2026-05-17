<?php

namespace App\Services\Payment;

use App\Helpers\CountryList;
use App\Helpers\PhonePrefixResolver;
use App\Models\Academy;
use App\Models\User;

/**
 * Resolves a user's country (ISO 3166-1 alpha-2) for payment gateway filtering.
 *
 * Resolution priority when a user is given:
 *   1. `student_profiles.phone_country` (ISO). Checked BEFORE the users
 *      column because the student profile is what the user edits through
 *      the profile page; `users.phone_country` is set once at registration
 *      and `SyncsPhoneCountryColumns` may have silently derived it from
 *      the historical `phone_country_code='+966'` NOT NULL DEFAULT, leaving
 *      a stale `SA` even after the user updated their real country on the
 *      profile form.
 *   2. `users.phone_country` (ISO).
 *   3. ISO derived from a raw `phone` string that carries an EXPLICIT
 *      international prefix (`+` or leading `00`). A bare `509150788` is a
 *      Saudi mobile, NOT Haiti (+509); the prefix gate guards against that.
 *   4. Ambiguity guard: if `phone_country_code='+966'` AND `phone_country`
 *      is NULL AND no phone prefix resolved a country AND the student
 *      profile declares a non-SA nationality, return the nationality. Last
 *      defence when phone signals are all corrupted or missing — matches
 *      the rule "phone first, nationality only when phone is missing".
 *   5. `phone_country_code` (user, then student profile) via dial-code map.
 *   6. Raw `phone` without an explicit prefix — last-chance fallback.
 *   7. Student profile declared nationality.
 *   8. `null` — never fall back to `$academy->country` when a user is given.
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

        $studentProfile = $user->studentProfile;
        if ($iso = $this->normalizeIso($studentProfile?->phone_country ?? null)) {
            return $iso;
        }

        if ($iso = $this->normalizeIso($user->phone_country ?? null)) {
            return $iso;
        }

        $userPhonePrefixIso = PhonePrefixResolver::isoFromExplicitPrefix($user->phone);
        if ($userPhonePrefixIso !== null) {
            return $userPhonePrefixIso;
        }

        $profilePhonePrefixIso = PhonePrefixResolver::isoFromExplicitPrefix($studentProfile?->phone);
        if ($profilePhonePrefixIso !== null) {
            return $profilePhonePrefixIso;
        }

        // Ambiguity guard for the `+966` poisoning incident: pre-migration the
        // `phone_country_code` column was `NOT NULL DEFAULT '+966'`, so almost
        // every legacy row carries `+966` regardless of the real country. When
        // the explicit phone prefix found nothing AND the new ISO column is
        // still NULL AND the student declared a non-SA nationality, trust the
        // nationality over the poisoned dial code. We only sidestep the dial
        // code in this exact pattern — genuine Saudi rows (nationality SA or
        // unset) fall through to the dial-code step below and still return SA.
        if (
            $user->phone_country_code === '+966'
            && $user->phone_country === null
            && $studentProfile !== null
            && ($nationalityIso = $this->normalizeIso($studentProfile->nationality)) !== null
            && $nationalityIso !== 'SA'
        ) {
            return $nationalityIso;
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

    private function normalizeIso(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $upper = strtoupper(trim($value));

        return CountryList::isValid($upper) ? $upper : null;
    }
}
