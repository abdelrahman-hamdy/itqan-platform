<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;

/**
 * End-to-end coverage of the boundary between sessions and subscriptions.
 *
 *   - useSession() decrements sessions_remaining + cycle row.
 *   - off-cycle session decrement only updates the cycle, not the subscription.
 *   - revert COMPLETED → SCHEDULED triggers reverseSubscriptionUsage →
 *     returnSession() (via BaseSessionObserver::updated()).
 *   - subscription_counted flag gives idempotency.
 *   - sessions_exhausted metadata flag set when remaining hits 0; returnSession
 *     unsets it (via BaseSubscription::returnSession()).
 *   - over-usage is logged but not blocked.
 *
 * The observer's full COMPLETED-pipeline runs through queued jobs; here we
 * exercise the model methods directly so the assertion is deterministic.
 * The cancel/uncomplete reverse path is observer-driven, so that branch uses
 * `update(['status' => …])` to fire the observer.
 *
 * See `app/Models/Traits/CountsTowardsSubscription.php`,
 * `app/Models/BaseSubscription.php` (useSession lines 1255–1331,
 * returnSession lines 1339–onwards), and `BaseSessionObserver::updated()`
 * (line 154-onwards) for the surface under test.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'count-int-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

/** Make a subscription with one materialized cycle. */
function subscriptionWithCycle(\App\Models\User $student, \App\Models\User $teacher, int $totalSessions = 8): array
{
    $sub = QuranSubscription::factory()
        ->forStudent($student)
        ->forTeacher($teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => $totalSessions,
            'total_sessions' => $totalSessions,
        ]);

    $cycle = $sub->ensureCurrentCycle();
    // ensureCurrentCycle() seeds total_sessions on the cycle if missing.
    if ($cycle->total_sessions === 0 || $cycle->total_sessions === null) {
        $cycle->update(['total_sessions' => $totalSessions]);
    }

    return [$sub->fresh(), $cycle->fresh()];
}

describe('useSession()', function () {
    it('SC1 — decrements sessions_remaining and bumps sessions_used + cycle row', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 8);

        $sub->useSession($cycle->id);

        $sub = $sub->fresh();
        $cycle = $cycle->fresh();
        expect($sub->sessions_used)->toBe(1);
        expect($sub->sessions_remaining)->toBe(7);
        expect($cycle->sessions_used)->toBe(1);
        expect($cycle->sessions_completed)->toBe(1);
    });

    it('SC2 — useSession on a non-current cycle ID updates only the cycle row, not the subscription row', function () {
        [$sub, $currentCycle] = subscriptionWithCycle($this->student, $this->teacher, 8);

        // Manufacture a *previous* cycle row attached to the same subscription.
        $oldCycle = SubscriptionCycle::create([
            'academy_id' => $this->academy->id,
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'cycle_number' => 0,
            'billing_cycle' => $sub->billing_cycle->value,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
            'total_sessions' => 8,
            'sessions_used' => 4,
            'sessions_completed' => 4,
        ]);

        $sub->useSession($oldCycle->id);

        $sub = $sub->fresh();
        $oldCycle = $oldCycle->fresh();
        expect($oldCycle->sessions_used)->toBe(5);
        // Subscription row is anchored to the CURRENT cycle and untouched.
        expect($sub->sessions_used)->toBe(0);
        expect($sub->sessions_remaining)->toBe(8);
    });

    it('SC3 — sessions_exhausted metadata flag is set when remaining hits 0', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 1);

        $sub->useSession($cycle->id);

        $fresh = $sub->fresh();
        expect($fresh->sessions_remaining)->toBe(0);
        expect($fresh->metadata['sessions_exhausted'] ?? false)->toBeTrue();
        expect($fresh->progress_percentage)->toEqual(100);
    });

    it('SC4 — returnSession() reverses the increment and clears sessions_exhausted', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 1);
        $sub->useSession($cycle->id);
        expect($sub->fresh()->metadata['sessions_exhausted'] ?? false)->toBeTrue();

        $sub->returnSession($cycle->id);

        $fresh = $sub->fresh();
        expect($fresh->sessions_used)->toBe(0);
        expect($fresh->sessions_remaining)->toBe(1);
        expect($fresh->metadata['sessions_exhausted'] ?? false)->toBeFalse();
    });

    it('SC5 — over-usage past zero is logged but does not block the count', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 1);

        $sub->useSession($cycle->id);
        // Already at 0 remaining. Spec says: log warning, allow.
        $sub->fresh()->useSession($cycle->id);

        $cycle = $cycle->fresh();
        expect($cycle->sessions_used)->toBe(2);
        // sessions_remaining clamps at 0, doesn't go negative.
        expect($sub->fresh()->sessions_remaining)->toBe(0);
    });
});

describe('subscription_counted flag (idempotency)', function () {
    it('SC6 — updateSubscriptionUsage() is idempotent — second call is a no-op', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 8);

        // Build a COMPLETED session attached to the cycle. Bypass observer
        // events to manufacture the COMPLETED status directly.
        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subMinutes(30),
            'subscription_counted' => false,
        ]));

        // First call counts.
        $session->updateSubscriptionUsage();
        expect($session->fresh()->subscription_counted)->toBeTrue();
        expect($sub->fresh()->sessions_used)->toBe(1);

        // Second call (idempotent — already counted).
        $session->fresh()->updateSubscriptionUsage();
        expect($sub->fresh()->sessions_used)->toBe(1);
    });
});

describe('observer-driven reverse flow', function () {
    it('SC7 — flipping a COMPLETED + counted session to SCHEDULED reverses the count via the observer', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 8);

        // First: a COMPLETED session that's been counted.
        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subMinutes(30),
            'subscription_counted' => true,
        ]));
        // Bake in the count manually.
        $sub->useSession($cycle->id);
        expect($sub->fresh()->sessions_used)->toBe(1);

        // Observer-fire path: status flip COMPLETED → SCHEDULED.
        $session->update([
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
        ]);

        // Observer's reverseSubscriptionAndEarnings ran returnSession.
        $fresh = $sub->fresh();
        expect($fresh->sessions_used)->toBe(0);
        expect($fresh->sessions_remaining)->toBe(8);
    });

    it('SC8 — flipping COMPLETED on a NOT-counted session is a no-op (no underflow)', function () {
        [$sub, $cycle] = subscriptionWithCycle($this->student, $this->teacher, 8);

        // COMPLETED session that was never marked subscription_counted.
        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subMinutes(30),
            'subscription_counted' => false,
        ]));
        // No useSession() call — sessions_used is 0.

        $session->update(['status' => SessionStatus::CANCELLED]);

        // Counter unchanged (didn't underflow).
        expect($sub->fresh()->sessions_used)->toBe(0);
        expect($sub->fresh()->sessions_remaining)->toBe(8);
    });
});
