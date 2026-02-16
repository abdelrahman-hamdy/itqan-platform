<?php

use App\Enums\Timezone;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;

test('session scheduled in UTC displays correctly for Riyadh academy', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);

    // Create session at 12:00 UTC (3:00 PM Riyadh time)
    $session = QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'scheduled_at' => Carbon::parse('2026-02-16 12:00:00', 'UTC'),
    ]);

    // Should display as 3:00 PM in Riyadh (UTC+3)
    AcademyContextService::setApiContext($academy);
    $displayTime = toAcademyTimezone($session->scheduled_at);

    expect($displayTime->format('H:i'))->toBe('15:00');
    expect(formatTimeArabic($displayTime))->toContain('3:');
});

test('session scheduled in UTC displays correctly for Cairo academy', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::CAIRO]);

    // Create session at 12:00 UTC (2:00 PM Cairo time)
    $session = QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'scheduled_at' => Carbon::parse('2026-02-16 12:00:00', 'UTC'),
    ]);

    AcademyContextService::setApiContext($academy);
    $displayTime = toAcademyTimezone($session->scheduled_at);

    expect($displayTime->format('H:i'))->toBe('14:00');
});

test('helper function toSaudiTime works correctly', function () {
    $utcTime = Carbon::parse('2026-02-16 12:00:00', 'UTC');
    $saudiTime = toSaudiTime($utcTime);

    expect($saudiTime->format('H:i'))->toBe('15:00');
    expect($saudiTime->timezone->getName())->toBe('Asia/Riyadh');
});

test('helper function formatSaudiTime works correctly', function () {
    $utcTime = Carbon::parse('2026-02-16 12:00:00', 'UTC');
    $formatted = formatSaudiTime($utcTime, 'H:i');

    expect($formatted)->toBe('15:00');
});

test('helper function parseSaudiTime works correctly', function () {
    $parsed = parseSaudiTime('2026-02-16 15:00:00');

    expect($parsed->timezone->getName())->toBe('Asia/Riyadh');
    expect($parsed->utc()->format('H:i'))->toBe('12:00');
});

test('helper function parseAndConvertToUtc works correctly', function () {
    // Parse time in Riyadh timezone and convert to UTC
    $parsed = parseAndConvertToUtc('2026-02-16 15:00:00', 'Asia/Riyadh');

    expect($parsed->timezone->getName())->toBe('UTC');
    expect($parsed->format('H:i'))->toBe('12:00');
});

test('nowInAcademyTimezone returns correct time for academy', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);
    AcademyContextService::setApiContext($academy);

    $now = nowInAcademyTimezone();
    expect($now->timezone->getName())->toBe('Asia/Riyadh');
});

test('getAcademyTimezone returns correct timezone for authenticated user', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::CAIRO]);
    $user = \App\Models\User::factory()->create(['academy_id' => $academy->id]);

    $this->actingAs($user);

    $tz = getAcademyTimezone();
    expect($tz)->toBe('Africa/Cairo');
});

test('session isReadyToStart uses academy timezone correctly', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);

    // Schedule session 1 hour in the future (academy timezone)
    $scheduledAt = nowInAcademyTimezone()->addHour();

    $session = QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'scheduled_at' => $scheduledAt->utc(),  // Store as UTC
        'status' => \App\Enums\SessionStatus::SCHEDULED,
    ]);

    AcademyContextService::setApiContext($academy);

    // Should not be ready to start (1 hour away)
    expect($session->isReadyToStart())->toBeFalse();
});

test('session can_cancel attribute uses academy timezone correctly', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);

    // Schedule session 3 hours in the future (more than 2-hour cancellation window)
    $scheduledAt = nowInAcademyTimezone()->addHours(3);

    $session = QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'scheduled_at' => $scheduledAt->utc(),
        'status' => \App\Enums\SessionStatus::SCHEDULED,
    ]);

    AcademyContextService::setApiContext($academy);

    // Should be able to cancel (more than 2 hours before)
    expect($session->can_cancel)->toBeTrue();
});

test('session can_reschedule attribute uses academy timezone correctly', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);

    // Schedule session 25 hours in the future (more than 24-hour reschedule window)
    $scheduledAt = nowInAcademyTimezone()->addHours(25);

    $session = QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'scheduled_at' => $scheduledAt->utc(),
        'status' => \App\Enums\SessionStatus::SCHEDULED,
    ]);

    AcademyContextService::setApiContext($academy);

    // Should be able to reschedule (more than 24 hours before)
    expect($session->can_reschedule)->toBeTrue();
});

test('formatTimeArabic uses academy timezone correctly', function () {
    $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);
    AcademyContextService::setApiContext($academy);

    // 12:00 UTC = 15:00 (3:00 PM) Riyadh time
    $utcTime = Carbon::parse('2026-02-16 12:00:00', 'UTC');
    $formatted = formatTimeArabic($utcTime);

    // Should show 3:00 PM in Arabic
    expect($formatted)->toContain('3:');
    expect($formatted)->toContain('مساءً');
});

test('formatTimeArabic handles midnight correctly', function () {
    $utcTime = Carbon::parse('2026-02-16 21:00:00', 'UTC'); // 00:00 Riyadh time
    $formatted = formatTimeArabic($utcTime, 'Asia/Riyadh');

    expect($formatted)->toContain('12:');
    expect($formatted)->toContain('منتصف الليل');
});

test('formatTimeArabic handles noon correctly', function () {
    $utcTime = Carbon::parse('2026-02-16 09:00:00', 'UTC'); // 12:00 Riyadh time
    $formatted = formatTimeArabic($utcTime, 'Asia/Riyadh');

    expect($formatted)->toContain('12:');
    expect($formatted)->toContain('ظهراً');
});

test('multi-academy timezone handling works correctly', function () {
    // Both sessions at 12:00 UTC
    $utcTime = Carbon::parse('2026-02-16 12:00:00', 'UTC');

    // Riyadh session should display as 15:00 (3 PM) in Asia/Riyadh timezone
    $riyadhDisplay = $utcTime->copy()->setTimezone('Asia/Riyadh');
    expect($riyadhDisplay->format('H:i'))->toBe('15:00');

    // Cairo session should display as 14:00 (2 PM) in Africa/Cairo timezone
    $cairoDisplay = $utcTime->copy()->setTimezone('Africa/Cairo');
    expect($cairoDisplay->format('H:i'))->toBe('14:00');

    // Verify toSaudiTime specifically converts to Riyadh timezone
    $saudiTime = toSaudiTime($utcTime);
    expect($saudiTime->format('H:i'))->toBe('15:00');
    expect($saudiTime->timezone->getName())->toBe('Asia/Riyadh');
});

test('timezone conversion does not mutate original Carbon instance', function () {
    $original = Carbon::parse('2026-02-16 12:00:00', 'UTC');
    $originalTimezone = $original->timezone->getName();

    // Convert to academy timezone
    $converted = toAcademyTimezone($original);

    // Original should still be UTC
    expect($original->timezone->getName())->toBe($originalTimezone);
    expect($original->format('H:i'))->toBe('12:00');
});

test('null datetime handling works correctly', function () {
    expect(toSaudiTime(null))->toBeNull();
    expect(formatSaudiTime(null))->toBe('');
    expect(toAcademyTimezone(null))->toBeNull();
    expect(parseAndConvertToUtc(null))->toBeNull();
});
