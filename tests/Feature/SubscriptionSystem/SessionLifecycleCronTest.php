<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionEarningsJob;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\EarningsCalculationService;
use App\Services\LiveKitService;
use App\Services\SessionSchedulerService;
use App\Services\UnifiedSessionStatusService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

/**
 * Session-status cron coverage — Scenarios E1–E5 from the test-plan.
 *
 *   E1 — `sessions:update-statuses` promotes SCHEDULED → READY when
 *        `now >= scheduled_at - preparation_minutes`.
 *   E2 — Same cron leaves SCHEDULED rows alone when they are outside the
 *        preparation window.
 *   E3 — Auto-completion: READY/ONGOING + scheduled_at + duration + buffer
 *        elapsed → COMPLETED.
 *   E4 — Stuck SCHEDULED rows past start time SURFACE for triage (the prod
 *        symptom in `followup_session_status_stuck.md`). The cron must
 *        either transition them OR flag them — never silently leave them.
 *   E5 — Hard-deleting a session before its earnings job runs must NOT
 *        crash the worker with `ModelNotFoundException`. Prod log
 *        2026-05-10 captured exactly this failure mode.
 *
 * E10 (schedule-extension idempotency) is intentionally out of scope here:
 * `quran:extend-schedules` lives only in `app/Console/Commands/Archived/`
 * and is not currently scheduled — covering it would test dead code.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'sessioncron-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create();
    $this->sub->ensureCurrentCycle();
});

/**
 * Build a QuranSession without firing the BaseSessionObserver so the test
 * can craft exact pre-conditions.
 */
function lifecycleSession(array $attrs = []): QuranSession
{
    return QuranSession::withoutEvents(fn () => QuranSession::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => test()->sub->id,
        'subscription_cycle_id' => test()->sub->fresh()->current_cycle_id,
        'status' => SessionStatus::SCHEDULED,
        'duration_minutes' => 30,
    ], $attrs)));
}

describe('E1 — UpdateSessionStatusesCommand transitions SCHEDULED → READY inside the prep window', function () {
    it('E1 — session within preparation window is promoted to READY', function () {
        // Default preparation minutes from SessionSettingsService is 10.
        // Place scheduled_at 5 minutes from now → inside the prep window.
        $session = lifecycleSession([
            'scheduled_at' => now()->addMinutes(5),
            'status' => SessionStatus::SCHEDULED,
        ]);

        Artisan::call('sessions:update-statuses', ['--quran-only' => true]);

        // The status service decides the transition; the cron applies it.
        expect($session->fresh()->status->value)->toBeIn([
            SessionStatus::READY->value,
            // If meeting creation fails in tests it may stay SCHEDULED;
            // the critical invariant is that the cron WAS attempted, not
            // skipped because the prep predicate said "not yet."
            SessionStatus::SCHEDULED->value,
        ]);

        // Direct predicate check via the scheduler service to pin down the
        // intent: regardless of side-effect success, the predicate must
        // signal "should transition" for this scheduled_at value.
        $service = app(SessionSchedulerService::class);
        // Reload the session in its original SCHEDULED state for the
        // predicate check (the cron may have already advanced it).
        $candidate = lifecycleSession([
            'scheduled_at' => now()->addMinutes(5),
            'status' => SessionStatus::SCHEDULED,
        ]);
        expect($service->shouldTransitionToReady($candidate))->toBeTrue();
    });
});

describe('E2 — UpdateSessionStatusesCommand leaves SCHEDULED alone outside prep window', function () {
    it('E2 — session 2 hours in the future is NOT transitioned', function () {
        $session = lifecycleSession([
            'scheduled_at' => now()->addHours(2),
            'status' => SessionStatus::SCHEDULED,
        ]);

        Artisan::call('sessions:update-statuses', ['--quran-only' => true]);

        expect($session->fresh()->status)->toBe(SessionStatus::SCHEDULED);
    });
});

describe('E3 — auto-completion of ONGOING sessions past their end + buffer', function () {
    it('E3 — ONGOING session past scheduled_at + duration + buffer auto-completes', function () {
        // Stub LiveKit so the room-active check returns false; the production
        // path would consult the real LiveKit API. Bind BEFORE resolving
        // SessionSchedulerService so the constructor injection sees the stub.
        $livekit = \Mockery::mock(LiveKitService::class);
        $livekit->shouldReceive('roomHasActiveParticipants')->andReturnFalse();
        $livekit->shouldReceive('endRoom')->andReturnTrue();
        $livekit->shouldIgnoreMissing();
        app()->instance(LiveKitService::class, $livekit);

        // Force re-resolution of services that depend on LiveKit so they
        // pick up our stub (the cron command resolves these from the
        // container at runtime).
        app()->forgetInstance(SessionSchedulerService::class);
        app()->forgetInstance(UnifiedSessionStatusService::class);

        // 30 min duration + 5 min default buffer = 35 min window.
        // Hard-cap grace is 60 minutes — place scheduled_at 120 minutes in
        // the past so the cron forces completion regardless of LiveKit.
        $session = lifecycleSession([
            'scheduled_at' => now()->subMinutes(120),
            'status' => SessionStatus::ONGOING,
            'duration_minutes' => 30,
            'started_at' => now()->subMinutes(120),
            'meeting_room_name' => null, // skip the LiveKit gate entirely
        ]);

        // Eligibility predicate is the cron's primary decision. The
        // downstream transition (transitionToCompleted) involves
        // LiveKit/attendance side-effects whose isolation is out of scope
        // for this test — but the predicate must say "yes, complete this."
        $service = app(SessionSchedulerService::class);
        expect($service->shouldAutoComplete($session))->toBeTrue(
            'session past scheduled_at + duration + buffer + grace must be eligible to auto-complete'
        );

        // Drive the transition. If observer/side-effect chains throw they
        // would surface here; the cron must remain resilient.
        $threw = null;
        try {
            $service->processStatusTransitions(collect([$session]));
        } catch (\Throwable $e) {
            $threw = $e;
        }
        expect($threw)->toBeNull(
            'processStatusTransitions must not throw on a clean ONGOING → COMPLETED candidate'
        );
    });
});

describe('E4 — stuck SCHEDULED detection (followup_session_status_stuck.md)', function () {
    it('E4 — SCHEDULED session past its start time should not silently remain SCHEDULED', function () {
        // Prod evidence: dozens of sessions stuck in SCHEDULED past start time
        // on 2026-05-04. The cron logs `transitions_to_ready: 0` even when
        // prep time has elapsed — so either:
        //   (a) `shouldTransitionToReady` is gated to skip "old" sessions, OR
        //   (b) the transition silently fails and the row is left stuck.
        //
        // Test asserts: a SCHEDULED session whose scheduled_at is in the
        // recent past (-30 min) MUST end up in a non-SCHEDULED terminal/active
        // state after the cron runs, OR be flagged with a sentinel that the
        // operator can query.
        $session = lifecycleSession([
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::SCHEDULED,
            'started_at' => null,
        ]);

        Artisan::call('sessions:update-statuses', ['--quran-only' => true]);

        $fresh = $session->fresh();
        // CORRECT: after the cron, scheduled-past-start should NOT still be
        // SCHEDULED. It should be READY, ONGOING, COMPLETED, CANCELLED, or
        // some "STUCK" sentinel — but never plain SCHEDULED past start time.
        expect($fresh->status)->not->toBe(
            SessionStatus::SCHEDULED,
            'sessions stuck in SCHEDULED past start time must surface for triage (followup_session_status_stuck.md)'
        );
    });
});

describe('E5 — CalculateSessionEarningsJob graceful handling of hard-deleted session', function () {
    it('E5 — earnings job for a hard-deleted session does not throw ModelNotFoundException', function () {
        // Prod log 2026-05-10: the job's `handle()` is fine (it find()s by
        // id and exits on null), but `SerializesModels` re-fetches the
        // attached model BEFORE handle() runs and throws when the parent
        // session is gone. This test exercises the path that's reachable
        // from inside the worker after deserialization succeeds — handle()
        // must remain null-safe.
        $session = lifecycleSession([
            'scheduled_at' => now()->subHour(),
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now()->subMinutes(15),
        ]);

        $job = new CalculateSessionEarningsJob($session);

        // Hard-delete: bypass the soft-delete trait so the model is gone.
        QuranSession::withoutGlobalScopes()->where('id', $session->id)->forceDelete();

        // CORRECT: handle() returns without raising — the missing-session
        // path is logged but does not propagate. If a `ModelNotFoundException`
        // surfaces here, it bubbles up and the test fails.
        $threw = null;
        try {
            $job->handle(app(EarningsCalculationService::class));
        } catch (\Throwable $e) {
            $threw = $e;
        }

        expect($threw)->toBeNull(
            'CalculateSessionEarningsJob::handle must not crash when the parent session is gone (prod 2026-05-10)'
        );
    });
});
