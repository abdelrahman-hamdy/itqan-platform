<?php

namespace App\Services\Payment;

use App\Helpers\CountryList;
use App\Models\Academy;
use App\Models\User;

/**
 * Resolves a user's country (ISO 3166-1 alpha-2) for payment gateway filtering.
 *
 * Resolution priority:
 *   1. Explicit dial-code column on the user row (most reliable — verifiable
 *      via SMS OTP and tied to a specific telecom operator).
 *   2. Explicit dial-code column on the student profile (students persist
 *      phone data on `student_profiles`, not always on `users`).
 *   3. Dial code parsed from a raw phone number string with a leading +/00
 *      prefix (legacy rows written before the unified phone component).
 *   4. Student profile declared nationality (self-declared fallback).
 *   5. Academy country (ultimate default).
 */
final class UserCountryResolver
{
    public function resolve(?User $user, ?Academy $academy = null): ?string
    {
        if ($user === null) {
            return $academy?->country?->value;
        }

        $iso = CountryList::dialCodeToIso($user->phone_country_code);
        if ($iso !== null) {
            return $iso;
        }

        $studentProfile = $user->studentProfile;
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

        $nationality = $studentProfile?->nationality;
        if (! empty($nationality)) {
            return strtoupper($nationality);
        }

        return $academy?->country?->value;
    }
}
