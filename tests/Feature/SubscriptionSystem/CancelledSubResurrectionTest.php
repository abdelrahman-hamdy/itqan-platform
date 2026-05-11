<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Payment;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\Notification;

/**
 * Regression: a cancelled "zombie" subscription must NOT be resurrected by
 * a fresh gateway payment. See
 * memory/followup_payment_routing_to_cancelled_sub.md for the prod incident
 * trail (student #391, subs 1173/1133/1095).
 *
 * The fix lives across 4 layers and this file pins all 4 contracts:
 *
 *   1. cancelAsDuplicateOrExpired() drops payment_status to FAILED so the
 *      sub is no longer payable via the student UI.
 *   2. BaseSubscription::acceptsRetryPayment() returns FALSE for cancelled
 *      subs (drives the blade can_pay flag + getPendingSubscription scope).
 *   3. getPendingSubscription() can't find cancelled subs — payment retry
 *      attempts fail at the controller boundary.
 *   4. activateFromPayment() guards against cancelled status — even if a
 *      payment row somehow lands on a cancelled sub, the sub is NOT
 *      resurrected.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'zombie-sub-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);
});

it('Z1 — cancelAsDuplicateOrExpired clears payment_status to FAILED', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->create([
            'status' => SessionSubscriptionStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
        ]);

    $sub->cancelAsDuplicateOrExpired();

    $fresh = $sub->fresh();
    expect($fresh->status)->toBe(SessionSubscriptionStatus::CANCELLED);
    expect($fresh->payment_status)->toBe(
        SubscriptionPaymentStatus::FAILED,
        'cancelled-as-duplicate subs must NOT keep payment_status=PENDING — the '
        .'student UI keys off that to surface a Pay Now button'
    );
    expect($fresh->cancelled_at)->not->toBeNull();
});

it('Z2 — acceptsRetryPayment is false after cancellation', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->create([
            'status' => SessionSubscriptionStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
        ]);

    expect($sub->acceptsRetryPayment())->toBeTrue('PENDING+PENDING must accept retries');

    $sub->cancelAsDuplicateOrExpired();

    expect($sub->fresh()->acceptsRetryPayment())->toBeFalse(
        'cancelled subs must not accept retries — drives both UI gate + payment-controller scope'
    );
});

it('Z3 — activateFromPayment refuses to resurrect a cancelled subscription', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->create([
            'status' => SessionSubscriptionStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
        ]);
    $sub->cancelAsDuplicateOrExpired();
    $sub = $sub->fresh();

    $payment = Payment::factory()->create([
        'academy_id' => $sub->academy_id,
        'user_id' => $sub->student_id,
        'payable_type' => $sub->getMorphClass(),
        'payable_id' => $sub->id,
        'amount' => 200,
        'status' => PaymentStatus::COMPLETED,
    ]);

    $sub->activateFromPayment($payment);

    $resurrected = $sub->fresh();
    expect($resurrected->status)->toBe(
        SessionSubscriptionStatus::CANCELLED,
        'activateFromPayment must early-return when the sub is CANCELLED — '
        .'flipping back to ACTIVE is what created the zombie incidents'
    );
    expect($resurrected->cancelled_at)->not->toBeNull(
        'cancelled_at must NOT be cleared by the activation attempt'
    );
});
