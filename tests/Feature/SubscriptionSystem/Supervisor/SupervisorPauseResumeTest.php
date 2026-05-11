<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;

/**
 * Asserts the *current* (broken) behavior of the supervisor pause/resume
 * endpoints — they bypass `BaseSubscription::pause()` / `resume()` by doing
 * a raw `$subscription->update(['status' => …])`.
 *
 * Tests document the gap (Bug #1 in `docs/subscription-bugs-found.md`).
 * After approval, each test gets a one-line edit to flip the assertion
 * direction once `changeStatus()` is rewritten to call the model methods.
 *
 * See `SupervisorSubscriptionsController::pause()` (line 282-285),
 * `::resume()` (line 287-290), and `::changeStatus()` (line 383-395).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'pause-test-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
});

describe('POST /manage/subscriptions/{type}/{id}/pause — Quran', function () {
    it('P1 — pause flips status ACTIVE → PAUSED in the database', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->active()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.pause', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertRedirect();
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::PAUSED);
    });

    it('P2 — pause stamps paused_at + pause_reason via model::pause() (Bug #1 fix)', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->active()
            ->create([
                'paused_at' => null,
                'pause_reason' => null,
            ]);

        $this->actingAs($this->admin)->post(
            route('manage.subscriptions.pause', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
        // Fix: the controller now delegates to BaseSubscription::pause(),
        // which stamps paused_at and pause_reason so resume() can later
        // compensate ends_at correctly.
        expect($fresh->paused_at)->not->toBeNull();
        expect($fresh->pause_reason)->not->toBeNull();
    });
});

describe('POST /manage/subscriptions/{type}/{id}/resume — Quran', function () {
    it('P3 — resume flips status PAUSED → ACTIVE on a manual pause', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->manuallyPaused()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.resume', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertRedirect();
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });

    it('P4 — resume compensates ends_at for the paused duration (Bug #1 fix)', function () {
        $teacher = createQuranTeacher($this->academy);
        // manuallyPaused() sets paused_at = 3h ago, ends_at = +25 days from now.
        // BaseSubscription::resume() adds the paused duration onto ends_at;
        // the supervisor controller now delegates to that path.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->manuallyPaused()
            ->create();
        $endsAtBefore = $sub->ends_at->copy();

        $this->actingAs($this->admin)->post(
            route('manage.subscriptions.resume', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        // Fix: ends_at extended by the paused duration so the student gets
        // back the time they lost. Compare with a small tolerance for the
        // few ms between scenarios setup and assertion.
        expect($fresh->ends_at->greaterThan($endsAtBefore))->toBeTrue(
            'ends_at must extend by the paused duration after resume()'
        );
        expect($fresh->paused_at)->toBeNull('paused_at must be cleared on resume');
    });

    it('P5 — resume restores SUSPENDED sessions in window (Bug #1 fix)', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->manuallyPaused()
            ->create();

        // A SUSPENDED session that BaseSubscription::resume() must restore
        // back to SCHEDULED. The controller now delegates to that path.
        $strandedSession = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $teacher->id,
            'quran_subscription_id' => $sub->id,
            'scheduled_at' => $sub->starts_at->copy()->addDays(10),
            'status' => SessionStatus::SUSPENDED,
        ]));

        $this->actingAs($this->admin)->post(
            route('manage.subscriptions.resume', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        // Fix: the session is restored to SCHEDULED so the student can
        // re-join. Previously left stranded as SUSPENDED.
        expect($strandedSession->fresh()->status)->toBe(SessionStatus::SCHEDULED);
    });
});

describe('POST /manage/subscriptions/{type}/{id}/{pause|resume} — Academic', function () {
    it('P6 — Academic pause + resume routes obey the same status-flip path', function () {
        $teacher = createAcademicTeacher($this->academy);
        $sub = AcademicSubscription::factory()
            ->withStudent($this->student)
            ->withTeacher($teacher)
            ->forAcademy($this->academy)
            ->active()
            ->create();

        // Pause
        $pauseResp = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.pause', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'academic',
                'subscription' => $sub->id,
            ])
        );
        $pauseResp->assertRedirect();
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::PAUSED);

        // Resume
        $resumeResp = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.resume', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'academic',
                'subscription' => $sub->id,
            ])
        );
        $resumeResp->assertRedirect();
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});
