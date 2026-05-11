<?php

declare(strict_types=1);

use App\Constants\PauseReason;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

/**
 * End-to-end coverage of the end-of-period flow:
 *
 *   subscriptions:expire-active   → flips ACTIVE → PAUSED with END_OF_PERIOD,
 *                                    suspends future sessions, notifies the
 *                                    student.
 *   subscriptions:advance-cycles  → promotes a queued cycle, clears paused_at /
 *                                    pause_reason, resets counters.
 *
 * Plus the bug-aware path: supervisor Resume on an end-of-period auto-paused
 * sub leaves SUSPENDED sessions stranded (Bug #1 in subscription-bugs-found.md).
 *
 * See `app/Console/Commands/ExpireActiveSubscriptions.php` and
 * `app/Console/Commands/AdvanceSubscriptionCycles.php`.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'eop-int-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

describe('subscriptions:expire-active end-to-end', function () {
    it('EOP1 — flips ACTIVE → PAUSED with END_OF_PERIOD AND suspends future SCHEDULED sessions', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->create([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->subDays(1),
                'paused_at' => null,
                'pause_reason' => null,
            ]);

        // Future SCHEDULED + past COMPLETED.
        [$future, $pastDone] = QuranSession::withoutEvents(function () use ($sub) {
            return [
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'quran_subscription_id' => $sub->id,
                    'scheduled_at' => now()->addDays(2),
                    'status' => SessionStatus::SCHEDULED,
                ]),
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'quran_subscription_id' => $sub->id,
                    'scheduled_at' => now()->subDays(5),
                    'status' => SessionStatus::COMPLETED,
                ]),
            ];
        });

        Artisan::call('subscriptions:expire-active', ['--force' => true]);

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($fresh->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
        expect($fresh->paused_at)->not->toBeNull();
        expect($future->fresh()->status)->toBe(SessionStatus::SUSPENDED);
        expect($pastDone->fresh()->status)->toBe(SessionStatus::COMPLETED);
    });
});

describe('subscriptions:advance-cycles promotion clears paused_at / pause_reason', function () {
    it('EOP2 — advance-cycles promotes a queued cycle, clears paused_at + pause_reason, resets counters', function () {
        // Build an ACTIVE sub whose current cycle ended yesterday and has a
        // queued cycle ready. Note: ExpireActiveSubscriptions skips this case
        // (queued cycle present) — AdvanceSubscriptionCycles handles it.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subDay(),
                'sessions_used' => 8,
                'sessions_remaining' => 0,
                'total_sessions' => 8,
            ]);

        // Existing current cycle ending yesterday.
        $currentCycle = SubscriptionCycle::create([
            'academy_id' => $this->academy->id,
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'cycle_number' => 1,
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'billing_cycle' => $sub->billing_cycle->value,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'total_sessions' => 8,
            'sessions_used' => 8,
            'sessions_completed' => 8,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        ]);
        $sub->update(['current_cycle_id' => $currentCycle->id]);

        // Queued cycle ready to roll.
        $queuedCycle = SubscriptionCycle::create([
            'academy_id' => $this->academy->id,
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'cycle_number' => 2,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'billing_cycle' => $sub->billing_cycle->value,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        ]);

        Artisan::call('subscriptions:advance-cycles');

        $fresh = $sub->fresh();
        // Counters reset.
        expect($fresh->sessions_used)->toBe(0);
        expect($fresh->sessions_remaining)->toBe(8);
        expect($fresh->paused_at)->toBeNull();
        expect($fresh->pause_reason)->toBeNull();
        // current_cycle_id swapped to the queued cycle.
        expect($fresh->current_cycle_id)->toBe($queuedCycle->id);
        expect($queuedCycle->fresh()->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
        expect($currentCycle->fresh()->cycle_state)->toBe(SubscriptionCycle::STATE_ARCHIVED);
    });
});

describe('Bug #1 follow-on: supervisor Resume on an end-of-period auto-paused sub', function () {
    it('EOP3 — supervisor Resume leaves SUSPENDED sessions stranded (raw update bypasses BaseSubscription::resume())', function () {
        // Setup: cron-driven auto-pause that suspends a future session.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->create([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->subDays(1),
            ]);
        $strandedSession = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'scheduled_at' => now()->addDays(3),
            'status' => SessionStatus::SCHEDULED,
        ]));
        Artisan::call('subscriptions:expire-active', ['--force' => true]);
        expect($strandedSession->fresh()->status)->toBe(SessionStatus::SUSPENDED);

        // Admin clicks Resume via supervisor frontend.
        $admin = createAdmin($this->academy);
        $this->actingAs($admin)->post(route('manage.subscriptions.resume', [
            'subdomain' => $this->academy->subdomain,
            'type' => 'quran',
            'subscription' => $sub->id,
        ]));

        // Bug #1: subscription is back to ACTIVE but the SUSPENDED session is
        // not restored — the raw `update(['status' => ACTIVE])` skips the
        // model's resume() side effects.
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($strandedSession->fresh()->status)->toBe(SessionStatus::SUSPENDED);
    });
});

describe('safeguard: cron skips when queued cycle exists', function () {
    it('EOP4 — expire-active skips a sub whose ends_at is past but has a queued cycle', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subDay(),
            ]);
        $currentCycle = SubscriptionCycle::create([
            'academy_id' => $this->academy->id,
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'cycle_number' => 1,
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'billing_cycle' => $sub->billing_cycle->value,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'total_sessions' => 8,
            'sessions_used' => 8,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        ]);
        $sub->update(['current_cycle_id' => $currentCycle->id]);
        SubscriptionCycle::create([
            'academy_id' => $this->academy->id,
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'cycle_number' => 2,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'billing_cycle' => $sub->billing_cycle->value,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'total_sessions' => 8,
            'sessions_used' => 0,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        ]);

        Artisan::call('subscriptions:expire-active', ['--force' => true]);

        // Skipped: queued cycle exists → AdvanceSubscriptionCycles handles it.
        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($fresh->paused_at)->toBeNull();
    });
});
