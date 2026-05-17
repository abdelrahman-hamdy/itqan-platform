<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Payment\UserCountryResolver;
use Illuminate\Support\Facades\DB;

/**
 * Covers the `+966` data-poisoning ambiguity guard added to
 * {@see UserCountryResolver::resolve()}.
 *
 * Background: pre-migration the `phone_country_code` column was
 * `NOT NULL DEFAULT '+966'`, so nearly every legacy row carries `+966`
 * regardless of the user's real country. The resolver must:
 *   - prefer student nationality when nationality is set AND non-SA AND
 *     no stronger signal (`phone_country` ISO / explicit phone prefix)
 *     contradicts it;
 *   - keep returning SA for genuine Saudi rows;
 *   - still trust an explicit `phone_country` ISO or `+`/`00` phone prefix
 *     over the nationality (priorities 1 and 2 unchanged).
 */
beforeEach(function () {
    $this->resolver = new UserCountryResolver;
    $this->academy = createAcademy();
});

function makeStudentRow(\App\Models\Academy $academy, array $userAttrs = [], array $profileAttrs = []): User
{
    $user = createStudent($academy);

    DB::table('users')->where('id', $user->id)->update(array_merge([
        'phone_country' => null,
        'phone_country_code' => '+966',
        'phone' => null,
    ], $userAttrs));

    if ($profileAttrs !== []) {
        DB::table('student_profiles')
            ->where('user_id', $user->id)
            ->update($profileAttrs);
    }

    return $user->fresh('studentProfile');
}

it('routes Palestinian-poisoned student to PS via the ambiguity guard', function () {
    $user = makeStudentRow(
        $this->academy,
        ['phone' => '599123456'],
        ['nationality' => 'PS', 'phone' => '599123456', 'phone_country' => null, 'phone_country_code' => '+966'],
    );

    expect($this->resolver->resolve($user))->toBe('PS');
});

it('still returns SA for a genuine Saudi student with SA nationality and +966 only', function () {
    $user = makeStudentRow(
        $this->academy,
        ['phone' => '512345678'],
        ['nationality' => 'SA', 'phone' => '512345678', 'phone_country' => null, 'phone_country_code' => '+966'],
    );

    expect($this->resolver->resolve($user))->toBe('SA');
});

it('trusts an explicit phone_country ISO over a non-SA nationality (priority 1)', function () {
    $user = makeStudentRow(
        $this->academy,
        ['phone' => '599123456', 'phone_country' => 'PS'],
        ['nationality' => 'EG', 'phone' => '599123456', 'phone_country' => 'PS', 'phone_country_code' => '+970'],
    );

    expect($this->resolver->resolve($user))->toBe('PS');
});

/**
 * شروق's actual pattern in production: she updated her profile so
 * student_profiles.phone_country = 'PS', but users.phone_country was
 * auto-derived to 'SA' at registration by SyncsPhoneCountryColumns from
 * the poisoned phone_country_code='+966' default and was never re-synced.
 * Edit page reads the (correct) student_profile column; resolver used to
 * read users first and returned SA. Must now return PS.
 */
it('prefers student_profile.phone_country over a stale users.phone_country', function () {
    $user = makeStudentRow(
        $this->academy,
        ['phone' => '599123456', 'phone_country' => 'SA', 'phone_country_code' => '+966'],
        ['phone' => '599123456', 'phone_country' => 'PS', 'phone_country_code' => '+970', 'nationality' => 'PS'],
    );

    expect($this->resolver->resolve($user))->toBe('PS');
});

it('trusts an explicit +970 phone prefix over a different nationality (priority 2)', function () {
    $user = makeStudentRow(
        $this->academy,
        ['phone' => '+970599123456', 'phone_country' => null, 'phone_country_code' => '+970'],
        ['nationality' => 'EG', 'phone' => '+970599123456', 'phone_country' => null, 'phone_country_code' => '+970'],
    );

    expect($this->resolver->resolve($user))->toBe('PS');
});

it('keeps returning SA once bare-local backfill writes phone_country=SA, even if poisoned dial code remains', function () {
    // Simulates the post-backfill state for a real Saudi: ISO column now set,
    // dial code still '+966'. Priority 1 (the ISO) must win — the ambiguity
    // guard is only reachable when phone_country IS NULL.
    $user = makeStudentRow(
        $this->academy,
        ['phone' => '512345678', 'phone_country' => 'SA'],
        ['nationality' => 'SA', 'phone' => '512345678', 'phone_country' => 'SA', 'phone_country_code' => '+966'],
    );

    expect($this->resolver->resolve($user))->toBe('SA');
});

it('returns null when no user is given and no academy is given', function () {
    expect($this->resolver->resolve(null))->toBeNull();
});

it('returns academy country for anonymous flows when academy is given', function () {
    expect($this->resolver->resolve(null, $this->academy))
        ->toBe($this->academy->country?->value);
});
