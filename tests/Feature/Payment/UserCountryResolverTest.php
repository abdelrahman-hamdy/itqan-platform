<?php

use App\Enums\Country;
use App\Models\Academy;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Payment\UserCountryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resolver = app(UserCountryResolver::class);
    $this->academy = Academy::factory()->create([
        'country' => Country::SAUDI_ARABIA,
    ]);
});

it('prefers explicit phone_country ISO over dial code', function () {
    $user = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'student',
        'phone' => '+15551234567',
        'phone_country_code' => '+1',
        'phone_country' => 'US',
    ]);

    // Even with a dial code that resolves to US, the explicit ISO column wins
    // — and it would still win if they disagreed.
    expect($this->resolver->resolve($user, $this->academy))->toBe('US');
});

it('falls back to dial-code map when phone_country ISO is missing', function () {
    $user = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'student',
        'phone' => '+15551234567',
        'phone_country_code' => '+1',
        'phone_country' => null,
    ]);

    // Disable the saving hook by forcing the column back to null after save
    // (the hook would have populated phone_country=US from phone_country_code=+1).
    DB::table('users')->where('id', $user->id)->update(['phone_country' => null]);
    $user->refresh();

    expect($this->resolver->resolve($user, $this->academy))->toBe('US');
});

it('returns null (not academy country) when a user has no country signals', function () {
    $user = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'student',
        // Phone with no resolvable leading dial code (no '+' / no '00', and
        // no '0-9' first character that maps onto a dial code).
        'phone' => '999999',
        'phone_country_code' => null,
        'phone_country' => null,
    ]);

    // Wipe out the auto-created student profile's nationality + phone fields
    // so we exercise the "no signal at all" branch. The User boot hook would
    // otherwise re-derive phone_country from phone_country_code on save.
    StudentProfile::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->update([
            'nationality' => null,
            'phone_country_code' => null,
            'phone_country' => null,
        ]);
    DB::table('users')->where('id', $user->id)->update([
        'phone_country_code' => null,
        'phone_country' => null,
    ]);

    $user->refresh();
    $user->load('studentProfile');

    expect($this->resolver->resolve($user, $this->academy))->toBeNull();
});

it('falls back to academy country only when no user is given', function () {
    expect($this->resolver->resolve(null, $this->academy))->toBe('SA');
});
