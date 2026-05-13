<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionRenewalService;
use Illuminate\Support\Facades\Notification;

/**
 * Renewal corner cases — Scenarios H1–H5 from the test-plan.
 *
 *   H1 — Renewing a sub that already has a PAID queued cycle throws
 *        "queued_cycle_exists" (the prod retry pattern from L2 log).
 *   H2 — Resubscribe on a CANCELLED sub places the new cycle starting NOW,
 *        NOT at the cancelled sub's cancelled_at + delta. Bug #9 origin.
 *   H3 — Renew with `billing_cycle` change (monthly → yearly) uses the
 *        NEW cycle's pricing AND duration consistently — no half-monthly,
 *        half-yearly hybrid.
 *   H4 — Renewal payment row is created and linked to the new cycle
 *        (`cycle.payment_id` set).
 *   H5 — Renew with `payment_mode = unpaid` enters grace; the cycle starts
 *        with `payment_status = pending` but the sub stays ACTIVE.
 *
 * SupervisorRenewResubscribeTest covers the HTTP/route layer; this file
 * targets the service-layer contracts that drive that route.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'renewedge-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    // Tenant context so renewal service's cycle lookups don't black-hole
    // under the academy global scope.
    setTenantContext($this->academy);

    $this->service = app(SubscriptionRenewalService::class);
});

/**
 * Helper: build an active subscription with a current cycle materialised.
 */
function renewSub(array $attrs = []): QuranSubscription
{
    $sub = QuranSubscription::factory()
        ->forStudent(test()->student)
        ->forTeacher(test()->teacher)
        ->active()
        ->create(array_merge([
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'total_sessions' => 8,
            'sessions_used' => 2,
            'sessions_remaining' => 6,
            // Default the current cycle to PAID. The H tests cover the
            // happy renewal path; routing for hybrid (active+pending)
            // subs is owned by the controller, not the renewal service —
            // see RenewBlocksUnpaidCurrentCycleTest for that contract.
            'payment_status' => SubscriptionPaymentStatus::PAID,
        ], $attrs));
    $sub->ensureCurrentCycle();

    return $sub->fresh();
}

describe('H1 — renew while a paid queued cycle already exists', function () {
    it('H1 — renew throws "queued_cycle_exists" when a PAID queued cycle is on the row', function () {
        $sub = renewSub();
        // Manually queue a paid cycle (simulates a prior renewal already
        // applied — the user clicks Renew a second time before the current
        // cycle has ended).
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

        $threw = null;
        try {
            $this->service->renew($sub->fresh(), [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'paid',
            ]);
        } catch (\Throwable $e) {
            $threw = $e;
        }

        expect($threw)->not->toBeNull(
            'renew on a sub with an existing paid queued cycle must throw — surfaces L2 prod log pattern'
        );
        // The service throws via `__('subscriptions.errors.queued_cycle_exists')`
        // which resolves to the Arabic translation in tests. Match against
        // the actual translation OR the key, whichever the test locale
        // produces. The critical invariant is that an exception fires.
        $expectedTranslation = __('subscriptions.errors.queued_cycle_exists');
        expect($threw->getMessage())->toBe(
            $expectedTranslation,
            'message must match the queued_cycle_exists translation'
        );
    });
});

describe('H2 — resubscribe on CANCELLED sub starts NOW', function () {
    it('H2 — resubscribed sub gets a new cycle whose starts_at is ~now, not offset', function () {
        $sub = renewSub();
        $cancelledAt = now()->subDays(5);
        $sub->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => $cancelledAt,
            'ends_at' => $cancelledAt->copy()->subDay(),
        ]);

        // validateTeacherAvailability uses QuranTeacherProfile::find($quran_teacher_id)
        // which looks up by primary key — but the column stores user_id. Pass
        // teacher_id explicitly to short-circuit the broken availability check
        // and isolate the H2 contract under test.
        $resubbed = $this->service->resubscribe($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
            'teacher_id' => $this->teacher->id,
        ]);

        $fresh = $resubbed->fresh()->load('currentCycle');
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);

        $cycle = $fresh->currentCycle;
        expect($cycle)->not->toBeNull('resubscribe must materialise a new active cycle');
        // CORRECT: starts_at is ~now, NOT cancelled_at + delta (Bug #9
        // root: the prod 686 starts_at was offset to the cancelled 685's
        // ends_at, surfacing 1 month later as a ghost sub).
        expect(abs($cycle->starts_at->diffInMinutes(now())))->toBeLessThan(
            5,
            'resubscribed cycle must start at NOW, not be offset to the cancelled sub\'s past end-date'
        );
    });
});

describe('H2b — resubscribe preserves prior cycle history', function () {
    it('H2b — resubscribe leaves the old cycles intact and assigns cycle_number = MAX + 1', function () {
        $sub = renewSub();
        // Cancel the sub but keep the original cycle in place. The pre-refactor
        // path used to wipe `current_cycle_id`; the new contract preserves the
        // archive row so we can audit history.
        $cancelledAt = now()->subDays(3);
        $sub->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => $cancelledAt,
            'ends_at' => $cancelledAt->copy()->subDay(),
        ]);

        $oldCycleIds = SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->pluck('id', 'cycle_number')
            ->all();
        $maxCycleNumberBefore = max(array_keys($oldCycleIds));

        $resubbed = $this->service->resubscribe($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
            'teacher_id' => $this->teacher->id,
        ]);

        $allCycles = SubscriptionCycle::query()
            ->where('subscribable_type', $resubbed->getMorphClass())
            ->where('subscribable_id', $resubbed->id)
            ->orderBy('cycle_number')
            ->get();

        // Prior cycles must still be present (not deleted, not orphaned).
        foreach ($oldCycleIds as $cycleNumber => $cycleId) {
            expect($allCycles->firstWhere('id', $cycleId))->not->toBeNull(
                "prior cycle #{$cycleNumber} ({$cycleId}) must remain in history after resubscribe"
            );
        }

        // The new active cycle must be at MAX(cycle_number) + 1.
        $newActive = $allCycles->firstWhere('cycle_state', SubscriptionCycle::STATE_ACTIVE);
        expect($newActive)->not->toBeNull('resubscribe must produce a new ACTIVE cycle');
        expect($newActive->cycle_number)->toBe(
            $maxCycleNumberBefore + 1,
            'new cycle_number must be monotonic (MAX + 1) so audit history stays unique-per-thread'
        );

        // The previous cycle must NOT have been promoted back to active.
        $previousCycleIds = array_values($oldCycleIds);
        foreach ($previousCycleIds as $previousId) {
            $previous = $allCycles->firstWhere('id', $previousId);
            expect($previous->cycle_state)->not->toBe(
                SubscriptionCycle::STATE_ACTIVE,
                'old cycles must not flip back to ACTIVE — only the new MAX+1 cycle is active'
            );
        }
    });
});

describe('H3 — billing_cycle change uses new cycle pricing AND duration', function () {
    it('H3 — switching monthly → yearly produces a yearly-length cycle, no mixed half-monthly window', function () {
        $sub = renewSub([
            'billing_cycle' => BillingCycle::MONTHLY,
            // Exhaust the current cycle so renew replaces immediately.
            'sessions_used' => 8,
            'sessions_remaining' => 0,
        ]);
        $sub->currentCycle->update([
            'sessions_used' => 8,
            'sessions_completed' => 8,
        ]);

        $renewed = $this->service->renew($sub->fresh(), [
            'billing_cycle' => 'yearly',
            'payment_mode' => 'paid',
        ]);

        $newCycle = $renewed->fresh()->load('currentCycle')->currentCycle;
        expect($newCycle)->not->toBeNull();
        expect($newCycle->billing_cycle)->toBe('yearly');
        // Yearly = ~12 months. starts_at→ends_at must span roughly 12 months.
        $diffMonths = (int) $newCycle->starts_at->diffInMonths($newCycle->ends_at);
        expect($diffMonths)->toBeGreaterThanOrEqual(
            11,
            'yearly cycle window must span ~12 months — not a hybrid leftover from the prior monthly cycle'
        );
    });
});

describe('H4 — renewal payment row links to the new cycle', function () {
    it('H4 — renew creates a Payment row whose id is stamped on cycle.payment_id', function () {
        $sub = renewSub([
            'sessions_used' => 8,
            'sessions_remaining' => 0,
        ]);
        $sub->currentCycle->update([
            'sessions_used' => 8,
            'sessions_completed' => 8,
        ]);

        $renewed = $this->service->renew($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
        ]);

        $cycle = $renewed->fresh()->load('currentCycle')->currentCycle;
        expect($cycle->payment_id)->not->toBeNull('renewal must mint a Payment row and link it to the cycle');

        $payment = Payment::find($cycle->payment_id);
        expect($payment)->not->toBeNull();
        // The renewal service uses payable_type/payable_id (polymorphic) to
        // link the payment to the subscription. The morph map normalizes
        // payable_type to the alias ('quran_subscription') rather than the
        // FQCN — assert against the configured morph alias.
        $sub = QuranSubscription::find($renewed->id);
        expect($payment->payable_type)->toBe($sub->getMorphClass());
        expect((int) $payment->payable_id)->toBe((int) $renewed->id);
        expect((int) $payment->subscription_cycle_id)->toBe((int) $cycle->id);
    });
});

describe('H5 — unpaid renewal enters grace', function () {
    it('H5 — payment_mode=unpaid leaves the new cycle with payment_status=pending', function () {
        $sub = renewSub([
            'sessions_used' => 8,
            'sessions_remaining' => 0,
        ]);
        $sub->currentCycle->update([
            'sessions_used' => 8,
            'sessions_completed' => 8,
        ]);

        $renewed = $this->service->renew($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'unpaid',
        ]);

        $cycle = $renewed->fresh()->load('currentCycle')->currentCycle;
        // CORRECT: cycle.payment_status reflects the unpaid mode; the
        // subscription row stays ACTIVE so the student isn't locked out
        // mid-cycle, but the new cycle is pending payment.
        expect($cycle->payment_status)->toBe(SubscriptionCycle::PAYMENT_PENDING);
        expect($renewed->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});
