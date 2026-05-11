<?php

declare(strict_types=1);

use App\Constants\PauseReason;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;

/**
 * Asserts the resume-vs-extend distinction (spec §1.3 + §3.D).
 *
 * Manual pauses (admin set pause_reason during a mid-period stop) keep the
 * existing resume() semantics: paused-duration is added back onto ends_at
 * to recover the lost time, suspended sessions are restored.
 *
 * End-of-period auto-pauses (cron stamps pause_reason = END_OF_PERIOD)
 * must NOT use Resume — its time-compensation is unearned in that case.
 * The Phase 2 fix hides the Resume button via an additional `->visible()`
 * predicate; resume() itself stays callable programmatically.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

describe('manual pause + Resume', function () {
    it('D1 — Resume on a manual pause adds the paused-duration back onto ends_at', function () {
        // Subscription manually paused 3 hours ago. ends_at sat 25 days out
        // when the admin paused. After Resume now, ends_at should advance
        // by ~3 hours.
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create();

        $endsAtBefore = $subscription->ends_at->copy();
        $pausedAt = $subscription->paused_at->copy();
        $expectedShift = (int) abs($pausedAt->diffInSeconds(now())); // ~3 hours

        $subscription->resume();

        expect($subscription->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        $shifted = (int) abs($endsAtBefore->diffInSeconds($subscription->fresh()->ends_at));
        // Allow small drift for the test harness's wall-clock time.
        expect($shifted)->toBeGreaterThanOrEqual($expectedShift - 5);
        expect($shifted)->toBeLessThanOrEqual($expectedShift + 5);
    });

    it('D3 — Resume restores SUSPENDED sessions whose date falls inside the new window', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create();

        // SUSPENDED sessions: one inside window, one before window. Bypass
        // the BaseSessionObserver subscription-active guard since we're
        // manufacturing the SUSPENDED state for test setup directly.
        [$insideWindow, $outsideWindow] = QuranSession::withoutEvents(function () use ($subscription) {
            return [
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'quran_subscription_id' => $subscription->id,
                    'scheduled_at' => $subscription->starts_at->copy()->addDays(10),
                    'status' => SessionStatus::SUSPENDED,
                ]),
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'quran_subscription_id' => $subscription->id,
                    'scheduled_at' => $subscription->starts_at->copy()->subDays(2),
                    'status' => SessionStatus::SUSPENDED,
                ]),
            ];
        });

        $subscription->resume();

        expect($insideWindow->fresh()->status)->toBe(SessionStatus::SCHEDULED);
        expect($outsideWindow->fresh()->status)->toBe(SessionStatus::SUSPENDED);
    });

    it('D4 — Resume clears pause_reason and paused_at', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create();

        $subscription->resume();

        $fresh = $subscription->fresh();
        expect($fresh->pause_reason)->toBeNull();
        expect($fresh->paused_at)->toBeNull();
    });
});

describe('end-of-period auto-pause + Resume', function () {
    it('D2 — auto-paused subscription is flagged with pause_reason = END_OF_PERIOD', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused()
            ->create();

        // The Phase 2 spec invariant: anything in PAUSED state with the
        // END_OF_PERIOD reason MUST be hidden from the Resume action.
        // The model itself doesn't enforce this (programmatic callers can
        // still resume); this test asserts the data-shape contract that
        // the visibility predicate (Phase 2 fix) reads.
        expect($subscription->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($subscription->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
    });
});
