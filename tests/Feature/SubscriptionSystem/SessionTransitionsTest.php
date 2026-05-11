<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\SessionSchedulerService;

/**
 * Regression for the prod incident in
 * memory/followup_session_status_stuck.md — dozens of SCHEDULED Quran
 * sessions sat past their preparation time without transitioning. The
 * predicate must:
 *
 *   - Accept SCHEDULED rows whose preparation window has started.
 *   - Reject anything not SCHEDULED, > maxFutureHours away, or > 24h past.
 *
 * If a future change widens or tightens a gate, the diagnostic payload makes
 * the failure source visible in the cron logs (added in
 * SessionSchedulerService::processStatusTransitions).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'sess-trans-'.uniqid()]);
    $this->teacher = createQuranTeacher($this->academy);
    $this->student = createStudent($this->academy);

    setTenantContext($this->academy);

    $this->scheduler = app(SessionSchedulerService::class);
});

function transitionSession(SessionStatus $status, \Carbon\Carbon $scheduledAt): QuranSession
{
    return QuranSession::factory()->create([
        'academy_id' => test()->academy->id,
        'quran_teacher_id' => test()->teacher->id,
        'student_id' => test()->student->id,
        'status' => $status,
        'scheduled_at' => $scheduledAt,
        'duration_minutes' => 60,
    ]);
}

it('S1 — SCHEDULED session past its preparation time is eligible to transition', function () {
    // Preparation default is 10 minutes; scheduled_at 5 minutes from now means
    // preparation_time = now - 5min, so now > preparation_time.
    $session = transitionSession(SessionStatus::SCHEDULED, now()->addMinutes(5));

    $diag = null;
    $result = $this->scheduler->shouldTransitionToReady($session, $diag);

    expect($result)->toBeTrue();
    expect($diag)->toBeNull('diagnostic must stay null on the happy path');
});

it('S2 — diagnostic surfaces wrong_status when a non-SCHEDULED row is queried', function () {
    $session = transitionSession(SessionStatus::ONGOING, now()->subMinutes(30));

    $diag = null;
    $result = $this->scheduler->shouldTransitionToReady($session, $diag);

    expect($result)->toBeFalse();
    expect($diag['gate'] ?? null)->toBe('wrong_status');
    expect($diag['status'] ?? null)->toBe('ongoing');
});

it('S3 — diagnostic surfaces too_far_future when scheduled_at exceeds maxFutureHours', function () {
    // getMaxFutureHours() is 24 — push to +48h.
    $session = transitionSession(SessionStatus::SCHEDULED, now()->addHours(48));

    $diag = null;
    $result = $this->scheduler->shouldTransitionToReady($session, $diag);

    expect($result)->toBeFalse();
    expect($diag['gate'] ?? null)->toBe('too_far_future');
});

it('S4 — diagnostic surfaces too_far_past when scheduled_at is > 24h ago', function () {
    $session = transitionSession(SessionStatus::SCHEDULED, now()->subHours(30));

    $diag = null;
    $result = $this->scheduler->shouldTransitionToReady($session, $diag);

    expect($result)->toBeFalse();
    expect($diag['gate'] ?? null)->toBe('too_far_past');
});

it('S5 — diagnostic surfaces before_preparation_time when prep hasn\'t started', function () {
    // 2 hours out — default prep is 10 minutes; preparation_time = scheduled_at - 10min,
    // which is still ~1h50m in the future.
    $session = transitionSession(SessionStatus::SCHEDULED, now()->addHours(2));

    $diag = null;
    $result = $this->scheduler->shouldTransitionToReady($session, $diag);

    expect($result)->toBeFalse();
    expect($diag['gate'] ?? null)->toBe('before_preparation_time');
    expect($diag['preparation_minutes'] ?? null)->toBe(10);
});
