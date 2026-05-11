<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionMaintenanceService;
use App\Services\Subscription\SubscriptionRenewalService;
use Illuminate\Support\Facades\Notification;

/**
 * Regression: the gate that blocks renew() when the current active cycle is
 * still unpaid. Added 2026-05-11 after the sub-772 incident where a student
 * who had not paid for her current cycle could keep clicking renew and stack
 * fresh manual-cash payment rows attached to FUTURE cycles, leaving the
 * current cycle un-payable through the normal student UI.
 *
 *   U1 — happy path:  current cycle PAID  → renew succeeds, queues a cycle.
 *   U2 — block path:  current cycle PENDING + ACTIVE → renew throws.
 *   U3 — exhausted:   sessions=0 + cycle pending → still blocked (no silent
 *                     archive of an unpaid cycle on the replace-now path).
 *   U4 — extend path: grace-period extension is unaffected by the gate.
 *   U5 — resubscribe: dormant-reactivation bypasses the gate via
 *                     `force_replace_now` even with an unpaid prior cycle.
 *
 * See: SubscriptionRenewalService::renew() — `current_cycle_unpaid` gate.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'unpaidgate-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);

    $this->renewal = app(SubscriptionRenewalService::class);
});

/**
 * Build an active subscription with a current cycle whose payment_status
 * mirrors `$paid`. Used by every test in this file.
 */
function subWithCycle(bool $paid, array $attrs = []): QuranSubscription
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
    // ensureCurrentCycle reads sub.payment_status to set cycle.payment_status,
    // but we also force the cycle column directly so the gate's read is
    // deterministic regardless of upstream materialization logic changes.
    $sub->currentCycle()->update([
        'payment_status' => $paid
            ? SubscriptionCycle::PAYMENT_PAID
            : SubscriptionCycle::PAYMENT_PENDING,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
    ]);

    return $sub->fresh();
}

describe('U1 — renew succeeds when current cycle is paid', function () {
    it('U1 — paid current cycle: renew() queues a new cycle and does not throw', function () {
        $sub = subWithCycle(paid: true);

        $renewed = $this->renewal->renew($sub, [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
        ]);

        // Either a queued or active new cycle is fine — the gate must not
        // have blocked. What we're asserting is "no exception, a new cycle
        // row was minted".
        $newCycles = SubscriptionCycle::query()
            ->where('subscribable_type', $renewed->getMorphClass())
            ->where('subscribable_id', $renewed->id)
            ->count();
        expect($newCycles)->toBeGreaterThanOrEqual(2, 'renew must materialise a second cycle');
    });
});

describe('U2 — renew is blocked when current cycle is unpaid (the regression)', function () {
    it('U2 — pending current cycle: renew() throws current_cycle_unpaid', function () {
        $sub = subWithCycle(paid: false);

        $threw = null;
        try {
            $this->renewal->renew($sub, [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'unpaid',
            ]);
        } catch (\Throwable $e) {
            $threw = $e;
        }

        expect($threw)->not->toBeNull(
            'renew on a sub whose current cycle is unpaid must throw — sub-772 regression'
        );
        expect($threw->getMessage())->toBe(
            __('subscriptions.errors.current_cycle_unpaid'),
            'exception message must be the localized current_cycle_unpaid string'
        );

        // No new cycle row should have been created.
        $cycleCount = SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->count();
        expect($cycleCount)->toBe(
            1,
            'blocked renew must not create a stacked future cycle'
        );
    });
});

describe('U3 — renew is blocked even when current cycle is exhausted', function () {
    it('U3 — sessions exhausted + cycle pending: still blocked, no silent archive', function () {
        $sub = subWithCycle(paid: false, attrs: [
            'sessions_used' => 8,
            'sessions_remaining' => 0,
        ]);
        $sub->currentCycle()->update([
            'sessions_used' => 8,
            'sessions_completed' => 8,
        ]);
        $sub = $sub->fresh();

        $threw = null;
        try {
            $this->renewal->renew($sub, [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'paid',
            ]);
        } catch (\Throwable $e) {
            $threw = $e;
        }

        expect($threw)->not->toBeNull(
            'replace-now path must not silently archive an unpaid current cycle'
        );
        expect($threw->getMessage())->toBe(
            __('subscriptions.errors.current_cycle_unpaid'),
        );

        // Original cycle is still ACTIVE — we did NOT archive it.
        $sub = $sub->fresh()->load('currentCycle');
        expect($sub->currentCycle->cycle_state)->toBe(
            SubscriptionCycle::STATE_ACTIVE,
            'rejected renew must leave the unpaid cycle in place (not archived)'
        );
    });
});

describe('U4 — extend (grace-period) is unaffected by the gate', function () {
    it('U4 — extend() on a sub with unpaid current cycle still adds grace', function () {
        $sub = subWithCycle(paid: false);

        $service = app(SubscriptionMaintenanceService::class);
        // extend signature is (BaseSubscription, int $graceDays, array $actor = [])
        $result = $service->extend($sub, 7, [
            'extended_by' => $this->student->id,
            'extended_by_name' => $this->student->name ?? 'student',
        ]);

        $sub = $sub->fresh();
        // Grace metadata is stamped — gate is service-local to renew(), not extend.
        expect($sub->metadata['grace_period_ends_at'] ?? null)
            ->not->toBeNull('extend() must succeed regardless of cycle payment_status');
    });
});

describe('U5 — resubscribe bypasses the gate via force_replace_now', function () {
    it('U5 — resubscribe on CANCELLED sub with unpaid prior cycle still succeeds', function () {
        $sub = subWithCycle(paid: false);
        // Flip to CANCELLED so resubscribe() accepts it. The current cycle
        // row is left as-is (STATE_ACTIVE, payment_status=PENDING) to mirror
        // production: cancel-the-sub doesn't archive the cycle.
        $cancelledAt = now()->subDays(2);
        $sub->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => $cancelledAt,
            'ends_at' => $cancelledAt->copy()->subDay(),
        ]);

        $resubbed = $this->renewal->resubscribe($sub->fresh(), [
            'billing_cycle' => 'monthly',
            'payment_mode' => 'paid',
            // validateTeacherAvailability looks up by primary key but the
            // column stores user_id. Pass explicit teacher_id to short-circuit.
            'teacher_id' => $this->teacher->id,
        ]);

        $fresh = $resubbed->fresh()->load('currentCycle');
        expect($fresh->status)->toBe(
            SessionSubscriptionStatus::ACTIVE,
            'resubscribe must reactivate even when the prior cycle was unpaid'
        );
        expect($fresh->currentCycle)->not->toBeNull(
            'resubscribe must produce a new active cycle (force_replace_now exempts the gate)'
        );
    });
});
