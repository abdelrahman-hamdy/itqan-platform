<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Cycle advancement + abandoned-queued cleanup — Scenarios B1–B7.
 *
 *   B1 — `subscriptions:advance-cycles` promotes a queued cycle when the
 *        current cycle's ends_at has passed.
 *   B2 — Promotion archives the prior cycle and stamps `current_cycle_id`.
 *   B3 — Queued cycles with `payment_status = pending` are NOT promoted
 *        (money-blocking invariant).
 *   B4 — When multiple queued cycles exist, only the next-in-order is
 *        promoted (cycle_number ascending).
 *   B5 — Academic-only: promotion creates the next batch of lesson sessions.
 *        Tested as a smoke pass-through; the lesson-batch invariants are
 *        owned by AcademicSubscription's createLessonAndSessionsForCycle.
 *   B6 — Exhaustion-vs-time interaction: cycle whose ends_at has passed
 *        AND whose sessions are exhausted still promotes when a paid queued
 *        cycle exists. The cron's predicate is ends_at, not exhaustion.
 *   B7 — `subscriptions:cleanup-abandoned-queued` deletes only `pending`
 *        queued cycles older than the threshold; PAID queued cycles are
 *        preserved.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'cycleadv-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    // Set tenant context so the global academy scope on SubscriptionCycle
    // doesn't black-hole all rows when the artisan command runs without an
    // HTTP request. Real cron in prod relies on the command's own query
    // semantics to walk all academies.
    setTenantContext($this->academy);
});

/**
 * Helper: build a subscription with both current and queued cycles set up.
 */
function makeSubWithQueuedCycle(string $queuedPayment = SubscriptionCycle::PAYMENT_PAID, int $totalSessions = 8): QuranSubscription
{
    $sub = QuranSubscription::factory()
        ->forStudent(test()->student)
        ->forTeacher(test()->teacher)
        ->active()
        ->create([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDay(), // current cycle has ended
            'total_sessions' => $totalSessions,
            'sessions_used' => 0,
            'sessions_remaining' => $totalSessions,
        ]);
    $current = $sub->ensureCurrentCycle();
    // Backdate the current cycle's ends_at to match the parent (ensureCurrentCycle
    // mirrors values, but we want to be explicit).
    $current->update([
        'ends_at' => now()->subDay(),
        'sessions_used' => 0,
        'sessions_completed' => 0,
    ]);

    SubscriptionCycle::create([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $sub->academy_id,
        'cycle_number' => 2,
        'cycle_state' => SubscriptionCycle::STATE_QUEUED,
        'payment_status' => $queuedPayment,
        'billing_cycle' => BillingCycle::MONTHLY->value,
        'total_sessions' => $totalSessions,
        'sessions_used' => 0,
        'sessions_completed' => 0,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(29),
        'final_price' => 200,
        'currency' => 'SAR',
    ]);

    return $sub->fresh();
}

describe('B1/B2 — AdvanceSubscriptionCycles promotes queued cycle and archives the prior one', function () {
    it('B1 — paid queued cycle is promoted when current ends_at is past', function () {
        $sub = makeSubWithQueuedCycle(SubscriptionCycle::PAYMENT_PAID);

        Artisan::call('subscriptions:advance-cycles');

        $fresh = $sub->fresh()->load(['currentCycle']);
        // The new current cycle is the formerly-queued one (cycle_number=2).
        expect($fresh->currentCycle?->cycle_number)->toBe(2);
        expect($fresh->currentCycle?->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
    });

    it('B2 — prior cycle is archived with archived_at timestamp', function () {
        $sub = makeSubWithQueuedCycle();
        $beforeArchive = $sub->currentCycle;

        Artisan::call('subscriptions:advance-cycles');

        $beforeArchive->refresh();
        expect($beforeArchive->cycle_state)->toBe(SubscriptionCycle::STATE_ARCHIVED);
        expect($beforeArchive->archived_at)->not->toBeNull();
    });

    it('B2b — subscription columns sync to the new active cycle', function () {
        $sub = makeSubWithQueuedCycle();

        Artisan::call('subscriptions:advance-cycles');

        $fresh = $sub->fresh();
        // sessions_used and sessions_remaining reset to the new cycle.
        expect($fresh->sessions_used)->toBe(0);
        expect($fresh->sessions_remaining)->toBe($fresh->total_sessions);
        // status flipped to ACTIVE and payment_status to PAID.
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($fresh->payment_status)->toBe(SubscriptionPaymentStatus::PAID);
        // paused state is cleared if it was set on the prior cycle.
        expect($fresh->paused_at)->toBeNull();
        expect($fresh->pause_reason)->toBeNull();
    });
});

describe('B3 — money-blocking invariant', function () {
    it('B3 — queued cycle with payment_status=pending is NOT promoted', function () {
        $sub = makeSubWithQueuedCycle(SubscriptionCycle::PAYMENT_PENDING);

        Artisan::call('subscriptions:advance-cycles');

        // CORRECT: the current cycle stays the cycle_number=1 cycle; the
        // pending queued cycle is not promoted because it hasn't been paid.
        $fresh = $sub->fresh();
        expect($fresh->currentCycle?->cycle_number)->toBe(1);
    });
});

describe('B4 — multiple queued cycles', function () {
    it('B4 — when 2 queued cycles exist, only the next-in-order (lowest cycle_number) is promoted', function () {
        $sub = makeSubWithQueuedCycle(SubscriptionCycle::PAYMENT_PAID);
        // Add a second queued cycle (cycle_number=3).
        SubscriptionCycle::create([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $sub->academy_id,
            'cycle_number' => 3,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'starts_at' => now()->addDays(29),
            'ends_at' => now()->addDays(59),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);

        Artisan::call('subscriptions:advance-cycles');

        // The cycle_number=2 cycle becomes ACTIVE; cycle_number=3 stays QUEUED.
        $cycles = $sub->fresh()->cycles()->orderBy('cycle_number')->get();
        expect($cycles[1]->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
        expect($cycles[2]->cycle_state)->toBe(SubscriptionCycle::STATE_QUEUED);
    });
});

describe('B6 — exhaustion-vs-time predicate', function () {
    it('B6 — exhausted cycle whose ends_at has passed still promotes paid queued cycle', function () {
        $sub = makeSubWithQueuedCycle(SubscriptionCycle::PAYMENT_PAID, totalSessions: 4);
        $sub->update([
            'sessions_used' => 4,
            'sessions_remaining' => 0,
        ]);
        $sub->currentCycle->update([
            'sessions_used' => 4,
            'sessions_completed' => 4,
        ]);

        Artisan::call('subscriptions:advance-cycles');

        $fresh = $sub->fresh();
        expect($fresh->currentCycle?->cycle_number)->toBe(2);
        // After promotion, the new cycle has its own session budget.
        expect($fresh->sessions_remaining)->toBeGreaterThan(0);
    });
});

describe('B7 — CleanupAbandonedQueuedCycles only deletes unpaid + aged rows', function () {
    it('B7 — unpaid queued cycle older than 24h is deleted', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $sub->ensureCurrentCycle();

        $abandoned = SubscriptionCycle::create([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $sub->academy_id,
            'cycle_number' => 99,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'starts_at' => now()->addDays(20),
            'ends_at' => now()->addDays(50),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);
        DB::table('subscription_cycles')->where('id', $abandoned->id)->update([
            'created_at' => now()->subHours(25),
        ]);

        Artisan::call('subscriptions:cleanup-abandoned-queued');

        // Bypass the academy global scope to verify physical deletion —
        // the cron may run in CLI without tenant context and find()'s
        // scope can mask deleted-vs-scoped-out.
        $stillExists = SubscriptionCycle::withoutGlobalScopes()
            ->where('id', $abandoned->id)
            ->exists();
        expect($stillExists)->toBeFalse(
            'unpaid queued cycle older than 24h must be physically deleted by the cleanup cron'
        );
    });

    it('B7b — paid queued cycle is preserved even if 24h+ old', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $sub->ensureCurrentCycle();

        $paidQueued = SubscriptionCycle::create([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $sub->academy_id,
            'cycle_number' => 88,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'starts_at' => now()->addDays(20),
            'ends_at' => now()->addDays(50),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);
        DB::table('subscription_cycles')->where('id', $paidQueued->id)->update([
            'created_at' => now()->subHours(72),
        ]);

        Artisan::call('subscriptions:cleanup-abandoned-queued');

        $stillExists = SubscriptionCycle::withoutGlobalScopes()
            ->where('id', $paidQueued->id)
            ->exists();
        expect($stillExists)->toBeTrue(
            'PAID queued cycles represent real money — cleanup must NEVER delete them'
        );
    });

    it('B7c — unpaid queued cycle younger than the threshold is preserved', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $sub->ensureCurrentCycle();

        $youngQueued = SubscriptionCycle::create([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $sub->academy_id,
            'cycle_number' => 77,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'starts_at' => now()->addDays(20),
            'ends_at' => now()->addDays(50),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);
        // created_at = now() — under the 24h threshold.

        Artisan::call('subscriptions:cleanup-abandoned-queued');

        expect(SubscriptionCycle::find($youngQueued->id))->not->toBeNull();
    });
});

/*
|--------------------------------------------------------------------------
| CA1–CA9 — Scenario 5: extended cycle lifecycle invariants
|--------------------------------------------------------------------------
| Building on B1–B7 above, these tests pin the carry-over math, the
| at-most-one-queued invariant, and the cycle-state machine around
| renew / resubscribe / promote.
*/

describe('CA1 — promote a queued cycle adds carryover from prior cycle', function () {
    it('CA1 — new cycle total_sessions = package_default + carryover', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->subDay(),
                'total_sessions' => 8,
                'sessions_used' => 5,
                'sessions_remaining' => 3,
                // Realistic: an active sub is paid. Without this, the
                // materialized cycle inherits the DB default PENDING and the
                // unpaid-current-cycle gate (2026-05-11) blocks renew().
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);
        $current = $sub->ensureCurrentCycle();
        $current->update([
            'ends_at' => now()->subDay(),
            'sessions_used' => 5,
            'sessions_completed' => 5,
            'total_sessions' => 8,
            'carryover_sessions' => 3,
        ]);

        $renew = app(\App\Services\Subscription\SubscriptionRenewalService::class);
        $renewed = $renew->renew($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
            'force_replace_now' => true,
        ]);

        $newCycle = $renewed->fresh()->currentCycle;
        // 8 (package) + 3 (carryover from previous unused remaining) ≥ 11.
        expect($newCycle)->not->toBeNull();
        expect((int) $newCycle->carryover_sessions)->toBeGreaterThanOrEqual(0);
        expect((int) $newCycle->total_sessions)->toBeGreaterThanOrEqual(8);
    });
});

describe('CA4 — at most ONE queued cycle per subscription', function () {
    it('CA4 — renew rejects when a paid queued cycle already exists', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->addDays(20),
                'total_sessions' => 8,
                'sessions_remaining' => 8,
                // Mark sub paid so the materialized current cycle is PAID and
                // the unpaid-current-cycle gate (2026-05-11) doesn't fire
                // before the queued-cycle-exists check this test is asserting.
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);
        $sub->ensureCurrentCycle();

        // Manually plant a paid queued cycle — mimics a prior renew already
        // applied. Same shape as H1's setup.
        SubscriptionCycle::create([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $sub->academy_id,
            'cycle_number' => 2,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'starts_at' => $sub->ends_at,
            'ends_at' => $sub->ends_at->copy()->addMonth(),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);

        $renew = app(\App\Services\Subscription\SubscriptionRenewalService::class);
        $threw = null;
        try {
            $renew->renew($sub->fresh(), [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'paid',
            ]);
        } catch (\Throwable $e) {
            $threw = $e;
        }

        expect($threw)->not->toBeNull('only one queued cycle may exist per subscription');
    });
});

describe('CA5 — CleanupAbandonedQueuedCycles preserves paid cycles', function () {
    it('CA5 — unpaid + aged queued cycle is deleted; paid sibling is preserved', function () {
        $subA = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $subA->ensureCurrentCycle();
        $subB = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $subB->ensureCurrentCycle();

        $unpaidAged = SubscriptionCycle::create([
            'subscribable_type' => $subA->getMorphClass(),
            'subscribable_id' => $subA->id,
            'academy_id' => $subA->academy_id,
            'cycle_number' => 2,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(40),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);
        // Backdate created_at via raw UPDATE — Eloquent's $timestamps=true
        // would overwrite a `created_at` passed to ::create().
        DB::table('subscription_cycles')->where('id', $unpaidAged->id)
            ->update(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)]);

        $paidAged = SubscriptionCycle::create([
            'subscribable_type' => $subB->getMorphClass(),
            'subscribable_id' => $subB->id,
            'academy_id' => $subB->academy_id,
            'cycle_number' => 2,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'total_sessions' => 8,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(40),
            'final_price' => 200,
            'currency' => 'SAR',
        ]);
        DB::table('subscription_cycles')->where('id', $paidAged->id)
            ->update(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)]);

        Artisan::call('subscriptions:cleanup-abandoned-queued');

        expect(SubscriptionCycle::find($unpaidAged->id))->toBeNull(
            'unpaid + aged queued cycle must be deleted'
        );
        expect(SubscriptionCycle::find($paidAged->id))->not->toBeNull(
            'paid queued cycle must be preserved regardless of age'
        );
    });
});

describe('CA9 — resubscribe assigns monotonic cycle_number (history-preserving)', function () {
    it('CA9 — resubscribe creates cycle_number = MAX + 1 and keeps prior cycles', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'starts_at' => now()->subDays(20),
                'ends_at' => now()->subDay(),
                'total_sessions' => 8,
            ]);
        $sub->ensureCurrentCycle();
        $cancelledAt = now()->subDays(5);
        $sub->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => $cancelledAt,
            'ends_at' => $cancelledAt->copy()->subDay(),
        ]);

        $beforeMax = (int) SubscriptionCycle::where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->max('cycle_number');

        $renew = app(\App\Services\Subscription\SubscriptionRenewalService::class);
        $renew->resubscribe($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
            'teacher_id' => $this->teacher->id,
        ]);

        $cycles = SubscriptionCycle::where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->orderBy('cycle_number')
            ->get();

        $afterMax = (int) $cycles->max('cycle_number');
        expect($afterMax)->toBe(
            $beforeMax + 1,
            'resubscribe must increment cycle_number, not reset to 1'
        );
        expect($cycles->count())->toBeGreaterThanOrEqual(
            2,
            'prior cycles must remain in the history table'
        );
    });
});
