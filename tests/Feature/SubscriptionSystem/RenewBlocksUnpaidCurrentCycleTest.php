<?php

declare(strict_types=1);

use App\Enums\SubscriptionPaymentStatus;
use App\Http\Controllers\StudentSubscriptionController;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionRenewalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Renew-flow intent routing — Scenarios R1–R5.
 *
 * Replaces the May-11 sub-772 gate that threw `current_cycle_unpaid` whenever
 * the current active cycle was still in payment_status=PENDING. That gate
 * blocked 131 prod subs whose current cycle was in the hybrid "active+pending"
 * shape (pre-May-11 cron damage, FixDoubleRenewalUnpaid output, and any new
 * "renew now, pay later" flow that didn't complete the gateway redirect).
 *
 * The new contract:
 *   R1 — Hybrid sub: controller redirects the student straight to the existing
 *        subscription-payment route. `renew()` is NOT called; no new cycle
 *        is created.
 *   R2 — Paid current cycle + remaining sessions: `renew()` queues a new
 *        cycle starting at current.ends_at (Rule 2: queue).
 *   R3 — Paid current cycle + exhausted sessions: `renew()` archives the
 *        current cycle and creates an active cycle starting today (Rule 1:
 *        replace).
 *   R4 — 60-second guard: a second rapid renew click on a paid sub is
 *        absorbed without minting a duplicate cycle.
 *   R5 — Hybrid sub: `QuranSubscriptionPaymentController::create()` accepts
 *        the sub (regression for the `getPendingSubscription()` loosening
 *        that allows paying off the active hybrid cycle).
 *
 * See: SubscriptionRenewalService::renew(),
 *      StudentSubscriptionController::processRenew(),
 *      QuranSubscriptionPaymentController::getPendingSubscription().
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'renewroute-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);

    $this->renewal = app(SubscriptionRenewalService::class);
});

/**
 * Build an active subscription with a current cycle whose payment_status
 * mirrors `$paid`. Used by every test in this file.
 */
function subWithCycleForRouting(bool $paid, array $attrs = []): QuranSubscription
{
    $sub = QuranSubscription::factory()
        ->forStudent(test()->student)
        ->forTeacher(test()->teacher)
        ->active()
        ->create(array_merge([
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(15),
            'total_sessions' => 8,
            'sessions_used' => 1,
            'sessions_remaining' => 7,
            'payment_status' => $paid
                ? SubscriptionPaymentStatus::PAID
                : SubscriptionPaymentStatus::PENDING,
        ], $attrs));
    $sub->ensureCurrentCycle();
    $sub->currentCycle()->update([
        'payment_status' => $paid
            ? SubscriptionCycle::PAYMENT_PAID
            : SubscriptionCycle::PAYMENT_PENDING,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
    ]);

    return $sub->fresh();
}

describe('R1 — controller routes hybrid renew clicks to the payment route', function () {
    it('R1 — hybrid sub: processRenew() redirects to quran.subscription.payment without calling renew()', function () {
        $sub = subWithCycleForRouting(paid: false);
        Auth::login($this->student);

        $controller = app(StudentSubscriptionController::class);
        $request = Request::create(
            "/student/subscriptions/quran/{$sub->id}/renew",
            'POST',
            ['billing_cycle' => 'monthly'],
        );
        $request->setUserResolver(fn () => $this->student);

        $response = $controller->processRenew(
            $request,
            $this->academy->subdomain,
            'quran',
            (string) $sub->id,
        );

        expect($response->getStatusCode())->toBe(302);
        expect($response->getTargetUrl())
            ->toContain("/quran/subscription/{$sub->id}/payment");

        // No new cycle row should have been created.
        $cycleCount = SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->count();
        expect($cycleCount)->toBe(
            1,
            'redirect-to-pay path must not stack a new cycle on top of the unpaid current cycle',
        );
    });
});

describe('R2 — paid current cycle + remaining sessions: queue a new cycle (Rule 2)', function () {
    it('R2 — paid + remaining sessions: renew() creates a queued cycle starting at current.ends_at', function () {
        $sub = subWithCycleForRouting(paid: true);

        $renewed = $this->renewal->renew($sub, [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
        ]);

        $cycles = SubscriptionCycle::query()
            ->where('subscribable_type', $renewed->getMorphClass())
            ->where('subscribable_id', $renewed->id)
            ->orderBy('cycle_number')
            ->get();

        expect($cycles)->toHaveCount(2, 'renew must materialise a second cycle');

        $current = $cycles->first();
        $queued = $cycles->last();

        expect($current->cycle_state)->toBe(
            SubscriptionCycle::STATE_ACTIVE,
            'original paid current cycle must remain ACTIVE — Rule 2 queues, does not replace',
        );
        expect($queued->cycle_state)->toBe(
            SubscriptionCycle::STATE_QUEUED,
            'new cycle must be queued behind the current one',
        );
        expect($queued->starts_at->equalTo($current->ends_at))->toBeTrue(
            'queued cycle must start at current.ends_at — not today',
        );
    });
});

describe('R3 — paid current cycle + exhausted sessions: replace today (Rule 1)', function () {
    it('R3 — paid + exhausted: renew() archives current and creates an active cycle starting today', function () {
        $sub = subWithCycleForRouting(paid: true, attrs: [
            'sessions_used' => 8,
            'sessions_remaining' => 0,
        ]);
        $sub->currentCycle()->update([
            'sessions_used' => 8,
            'sessions_completed' => 8,
        ]);
        $sub = $sub->fresh();
        $oldCycleId = $sub->currentCycle->id;

        $renewed = $this->renewal->renew($sub, [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
        ]);

        $oldCycle = SubscriptionCycle::find($oldCycleId);
        expect($oldCycle->cycle_state)->toBe(
            SubscriptionCycle::STATE_ARCHIVED,
            'exhausted current cycle must be archived on replace-now',
        );

        $newCurrent = $renewed->fresh()->currentCycle;
        expect($newCurrent)->not->toBeNull('a new active current cycle must exist');
        expect($newCurrent->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
        expect($newCurrent->starts_at->isToday())->toBeTrue(
            'replace-now path must start the new cycle today, not at the prior cycle\'s ends_at',
        );
    });
});

describe('R4 — 60-second rapid renewal guard catches double-submit', function () {
    it('R4 — second click within 60s: returns subscription unchanged, no duplicate cycle', function () {
        $sub = subWithCycleForRouting(paid: true);

        $firstRenew = $this->renewal->renew($sub, [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
        ]);

        $countAfterFirst = SubscriptionCycle::query()
            ->where('subscribable_type', $firstRenew->getMorphClass())
            ->where('subscribable_id', $firstRenew->id)
            ->count();

        // Second click immediately after — within the 60-sec window.
        $secondRenew = $this->renewal->renew($firstRenew, [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
        ]);

        $countAfterSecond = SubscriptionCycle::query()
            ->where('subscribable_type', $secondRenew->getMorphClass())
            ->where('subscribable_id', $secondRenew->id)
            ->count();

        expect($countAfterSecond)->toBe(
            $countAfterFirst,
            '60-sec rapid-renewal guard must absorb the second click — no duplicate cycle',
        );
    });
});

describe('R5 — hybrid sub satisfies the payment-eligibility predicate', function () {
    it('R5 — hybrid sub: isCurrentCyclePaymentPending OR acceptsRetryPayment must hold so getPendingSubscription accepts it', function () {
        $sub = subWithCycleForRouting(paid: false);

        // getPendingSubscription() in both payment controllers gates on
        // ($sub->acceptsRetryPayment() || $sub->isCurrentCyclePaymentPending()).
        // The model-level disjunction is the actual contract — assert it
        // directly instead of reaching through controller internals.
        expect($sub->isCurrentCyclePaymentPending() || $sub->acceptsRetryPayment())->toBeTrue(
            'hybrid sub must satisfy the payment-eligibility predicate so the student can pay off the current cycle',
        );
    });
});
