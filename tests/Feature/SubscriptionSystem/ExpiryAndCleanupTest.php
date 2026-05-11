<?php

declare(strict_types=1);

use App\Constants\PauseReason;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Expiry + cleanup coverage — Scenarios C1–C5 from the test-plan.
 *
 *   C1 — `subscriptions:expire-active` flips ACTIVE+ends_at_past → PAUSED when
 *        there is no grace AND no queued cycle.
 *   C2 — Same cron leaves rows in an active grace period alone.
 *   C3 — Same cron leaves rows with a queued cycle alone (advance-cycles owns
 *        that path).
 *   C4 — End-of-period pause stamps `pause_reason = END_OF_PERIOD` (the J2
 *        flag the Filament Resume button gates on).
 *   C5 — `subscriptions:cleanup-expired-pending` must flip BOTH `status` AND
 *        `payment_status` to CANCELLED. Today it only flips `status` — the
 *        zombie-resurrection bug from `feedback_payment_routing_to_cancelled_sub.md`
 *        leaks here. **Expected to FAIL** when run today; that failure is the
 *        new bug to log.
 *
 * EndOfPeriodPauseTest already covers C1/C2/C4. This file fills the gap on
 * C3 and the C5 zombie-population invariant.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'expirecleanup-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

describe('C1 — ExpireActiveSubscriptions: ACTIVE+ends_at_past with no queued + no grace → PAUSED', function () {
    it('C1 — flips status and stamps END_OF_PERIOD pause reason', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->create([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->subDay(),
                'paused_at' => null,
                'pause_reason' => null,
            ]);

        Artisan::call('subscriptions:expire-active', ['--force' => true]);

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($fresh->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
        expect($fresh->paused_at)->not->toBeNull();
    });
});

describe('C3 — ExpireActiveSubscriptions skips rows with a queued cycle', function () {
    it('C3 — sub with queued cycle is left for AdvanceSubscriptionCycles, not paused', function () {
        // The cron contract: if a queued cycle is waiting, the *other* cron
        // (advance-cycles) promotes it; expire-active must not touch the row.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->create([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->subDay(),
            ]);
        $sub->ensureCurrentCycle();

        // Mint a queued cycle manually so we don't depend on the renewal
        // service's invariants in this assertion.
        SubscriptionCycle::create([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $sub->academy_id,
            'cycle_number' => 2,
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'billing_cycle' => $sub->billing_cycle?->value ?? 'monthly',
            'total_sessions' => $sub->total_sessions,
            'sessions_used' => 0,
            'sessions_completed' => 0,
            'starts_at' => $sub->ends_at,
            'ends_at' => $sub->ends_at->copy()->addMonth(),
            'final_price' => $sub->final_price ?? 200,
            'currency' => $sub->currency ?? 'SAR',
        ]);

        Artisan::call('subscriptions:expire-active', ['--force' => true]);

        // CORRECT: still ACTIVE — the queued cycle is the advance-cycles
        // cron's responsibility.
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});

describe('C5 — getPendingSubscription zombie-routing guard', function () {
    it('C5 — after cleanup the cancelled sub is invisible to the payment retry route (Bug #12 fix)', function () {
        // Re-framed from the old "payment_status must flip to CANCELLED"
        // expectation: payment_status=PENDING on a cancelled sub is
        // semantically accurate (the payment never landed, never will).
        // The actual guard is at the read site — `getPendingSubscription`
        // must require status=PENDING too, so a cancelled-PENDING row can
        // no longer be revived by a stray gateway payment.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create([
                'payment_status' => SubscriptionPaymentStatus::PENDING,
            ]);
        DB::table('quran_subscriptions')
            ->where('id', $sub->id)
            ->update(['created_at' => now()->subHours(72)]);

        Artisan::call('subscriptions:cleanup-expired-pending', ['--force' => true]);

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::CANCELLED);

        // After the cleanup, the payment-retry route must NOT find this row.
        // We assert the contract directly against the private finder via
        // reflection so we don't depend on the auth/HTTP harness here.
        $controller = new \App\Http\Controllers\QuranSubscriptionPaymentController(
            app(\App\Services\PaymentService::class),
            app(\App\Services\Payment\AcademyPaymentGatewayFactory::class)
        );
        $method = (new \ReflectionClass($controller))->getMethod('getPendingSubscription');
        $method->setAccessible(true);

        // Auth as the owning student so the inner where('student_id', Auth::id()) hits.
        $this->actingAs($this->student);

        $found = $method->invoke($controller, $this->academy, $sub->id);

        expect($found)->toBeNull(
            'cancelled-PENDING subs must not be returned by the payment retry route'
        );
    });

    it('C5b — cleanup also cancels the associated pending Payment rows', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create();
        DB::table('quran_subscriptions')
            ->where('id', $sub->id)
            ->update(['created_at' => now()->subHours(72)]);

        $payment = \App\Models\Payment::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
            'subscription_id' => $sub->id,
            'payable_type' => QuranSubscription::class,
            'payable_id' => $sub->id,
            'payment_code' => 'EXP-'.uniqid(),
            'payment_method' => 'credit_card',
            'payment_gateway' => 'paymob',
            'payment_type' => 'subscription',
            'amount' => 200,
            'net_amount' => 200,
            'currency' => 'SAR',
            'tax_amount' => 0,
            'tax_percentage' => 0,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        Artisan::call('subscriptions:cleanup-expired-pending', ['--force' => true]);

        $freshPayment = $payment->fresh();
        // CORRECT: the cleanup service already does this (line 114-119 of
        // SubscriptionMaintenanceService) — payment row cancelled alongside.
        // This test pins it down so a refactor doesn't drop the behavior.
        expect($freshPayment->status->value ?? $freshPayment->status)
            ->toBe('cancelled');
    });

    it('C5c — cleanup leaves grace-period subs alone even if 72h+ old', function () {
        // The `expiredPending` scope respects admin-granted grace metadata.
        // A 72h-old PENDING with a future grace_period_ends_at MUST NOT be
        // cancelled — operator intent overrides the cleanup timer.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create([
                'metadata' => [
                    'grace_period_ends_at' => now()->addDays(3)->toDateTimeString(),
                ],
            ]);
        DB::table('quran_subscriptions')
            ->where('id', $sub->id)
            ->update(['created_at' => now()->subHours(72)]);

        Artisan::call('subscriptions:cleanup-expired-pending', ['--force' => true]);

        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::PENDING);
    });
});

describe('C-extra — cleanup is a no-op when nothing has aged past the threshold', function () {
    it('leaves recently created PENDING subs alone (under 24h)', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create();
        // created_at = now() by default → not eligible for cleanup.

        Artisan::call('subscriptions:cleanup-expired-pending', ['--force' => true]);

        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::PENDING);
    });
});
