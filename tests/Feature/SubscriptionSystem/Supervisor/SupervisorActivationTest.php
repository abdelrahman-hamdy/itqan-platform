<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Payment;
use App\Models\QuranSubscription;

/**
 * Asserts the activation surface — `POST /activate` and `POST /confirm-payment`.
 *
 *   - `activate()` calls `BaseSubscription::activate()` (not raw update).
 *     This is the *correct* path; pause/resume — Bug #1 — are separate.
 *   - `confirmPayment()` delegates to `PaymentReconciliationService::confirmPaymentAndActivate`,
 *     which marks the Payment COMPLETED and stamps subscription dates.
 *
 * See `SupervisorSubscriptionsController::activate()` (line 268-280) and
 * `::confirmPayment()` (line 490-512).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'activate-test-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
});

describe('POST /manage/subscriptions/{type}/{id}/activate', function () {
    it('AC1 — activate flips PENDING → ACTIVE and stamps payment_status = paid', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->pending()
            ->create([
                'starts_at' => null,
                'ends_at' => null,
            ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.activate', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertRedirect();
        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($fresh->payment_status)->toBe(SubscriptionPaymentStatus::PAID);
        expect($fresh->starts_at)->not->toBeNull();
        expect($fresh->ends_at)->not->toBeNull();
    });

    it('AC2 — activate is unguarded — calling on an ACTIVE sub silently re-stamps dates (latent risk)', function () {
        // BaseSubscription::activate() has no canActivate guard. Calling it on
        // an already-ACTIVE row will:
        //   - set payment_status = PAID (no-op if already PAID)
        //   - keep starts_at if set, else now()
        //   - RECALCULATE ends_at = billing_cycle->calculateEndDate(starts_at)
        //   - reset last_payment_date = now()
        //
        // The supervisor route has no UI guard either — if the button is
        // exposed on a non-pending sub (e.g., misconfigured Blade), the date
        // recalculation could shorten coverage. Test pins the current shape.
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->active()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.activate', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertRedirect();
        // No exception — status stays ACTIVE.
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});

describe('POST /manage/subscriptions/{type}/{id}/confirm-payment', function () {
    it('AC3 — confirm-payment marks the latest pending Payment as COMPLETED', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->pending()
            ->create();

        // A pending payment exists — typical of mobile/web flows that drop
        // the user back without webhook confirmation.
        $payment = Payment::createPayment([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
            'payable_type' => QuranSubscription::class,
            'payable_id' => $sub->id,
            'payment_method' => 'cash',
            'payment_gateway' => 'manual',
            'amount' => 200.00,
            'currency' => 'SAR',
            'status' => PaymentStatus::PENDING,
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.confirm-payment', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ]),
            ['payment_reference' => 'CASH-RECEIPT-001']
        );

        $response->assertRedirect();
        $freshPayment = $payment->fresh();
        expect($freshPayment->status)->toBe(PaymentStatus::COMPLETED);
        expect($freshPayment->confirmed_at)->not->toBeNull();
        // Subscription is also activated.
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });

    it('AC4 — confirm-payment without an existing payment creates a manual one and activates', function () {
        $teacher = createQuranTeacher($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($teacher)
            ->pending()
            ->create();

        expect($sub->payments()->count())->toBe(0);

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.confirm-payment', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertRedirect();
        // PaymentReconciliationService minted a manual cash payment because
        // none existed.
        expect($sub->fresh()->payments()->count())->toBe(1);
        $payment = $sub->fresh()->payments()->first();
        expect($payment->status)->toBe(PaymentStatus::COMPLETED);
        expect($payment->payment_gateway)->toBe('manual');
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});
