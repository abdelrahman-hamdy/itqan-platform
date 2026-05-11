<?php

declare(strict_types=1);

use App\Constants\PauseReason;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

/**
 * Asserts the auto-pause cron behavior (`subscriptions:expire-active`).
 *
 * The cron must:
 *   - flip ACTIVE → PAUSED with pause_reason = END_OF_PERIOD when the paid
 *     window has ended and there is no grace and no queued cycle (C1)
 *   - skip when grace_period_ends_at is in the future (C2)
 *   - skip when a queued_cycle exists (C3 — AdvanceSubscriptionCycles will
 *     handle it)
 *   - suspend future SCHEDULED/UNSCHEDULED/READY sessions (C4)
 *
 * See docs/subscription-behavior-spec.md §3.C.
 */
beforeEach(function () {
    // FCM/push delivery is environment-dependent; faking it isolates the
    // cron logic and prevents notification exceptions from rolling back the
    // pause transaction.
    Notification::fake();

    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

it('C1 — flips ACTIVE → PAUSED with pause_reason = END_OF_PERIOD when ends_at is past', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->create([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(2),
            'paused_at' => null,
            'pause_reason' => null,
        ]);

    Artisan::call('subscriptions:expire-active', ['--force' => true]);

    $fresh = $subscription->fresh();
    expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
    expect($fresh->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
    expect($fresh->paused_at)->not->toBeNull();
});

it('C2 — skips subscription when grace period is active', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->inGracePeriod(7)
        ->create();

    Artisan::call('subscriptions:expire-active', ['--force' => true]);

    expect($subscription->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
});

it('C4 — suspends future scheduled sessions when auto-pausing', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->create([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(2),
        ]);

    // One SCHEDULED future session, one COMPLETED past session.
    // Bypass BaseSessionObserver since we're crafting test fixtures directly.
    [$futureSession, $pastCompleted] = QuranSession::withoutEvents(function () use ($subscription) {
        return [
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'quran_subscription_id' => $subscription->id,
                'scheduled_at' => now()->addDays(3),
                'status' => SessionStatus::SCHEDULED,
            ]),
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'quran_subscription_id' => $subscription->id,
                'scheduled_at' => now()->subDays(10),
                'status' => SessionStatus::COMPLETED,
            ]),
        ];
    });

    Artisan::call('subscriptions:expire-active', ['--force' => true]);

    expect($futureSession->fresh()->status)->toBe(SessionStatus::SUSPENDED);
    // Past completed sessions are not touched.
    expect($pastCompleted->fresh()->status)->toBe(SessionStatus::COMPLETED);
});

it('C5 — sends SubscriptionExpiredNotification to the student', function () {
    \Illuminate\Support\Facades\Notification::fake();

    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->create([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(1),
        ]);

    Artisan::call('subscriptions:expire-active', ['--force' => true]);

    \Illuminate\Support\Facades\Notification::assertSentTo(
        $subscription->student,
        \App\Notifications\SubscriptionExpiredNotification::class,
    );
});

it('I3-cron — leaves not-yet-expired ACTIVE subscriptions alone', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create();
    // Sanity: factory's active() puts ends_at 20 days in the future.
    expect($subscription->ends_at->isFuture())->toBeTrue();

    Artisan::call('subscriptions:expire-active', ['--force' => true]);

    expect($subscription->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($subscription->fresh()->pause_reason)->toBeNull();
});

it('does not double-pause a subscription already in PAUSED', function () {
    // Scenario: cron runs again after previous pause. Should be a no-op.
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->autoPaused()
        ->create();

    $pausedAtBefore = $subscription->paused_at->copy();

    Artisan::call('subscriptions:expire-active', ['--force' => true]);

    // paused_at unchanged — the cron only operates on ACTIVE rows.
    $fresh = $subscription->fresh();
    expect($fresh->paused_at->equalTo($pausedAtBefore))->toBeTrue();
    expect($fresh->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
});
