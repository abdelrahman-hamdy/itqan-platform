<?php

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use Carbon\Carbon;

// ========================================
// SessionSubscriptionStatus Tests
// ========================================

test('canAccess returns true only for ACTIVE', function () {
    expect(SessionSubscriptionStatus::ACTIVE->canAccess())->toBeTrue();

    $nonAccessible = [
        SessionSubscriptionStatus::PENDING,
        SessionSubscriptionStatus::PAUSED,
        SessionSubscriptionStatus::SUSPENDED,
        SessionSubscriptionStatus::CANCELLED,
    ];

    foreach ($nonAccessible as $status) {
        expect($status->canAccess())->toBeFalse("Expected {$status->value} to NOT allow access");
    }
});

test('canPause returns true only for ACTIVE', function () {
    expect(SessionSubscriptionStatus::ACTIVE->canPause())->toBeTrue();

    $nonPausable = [
        SessionSubscriptionStatus::PENDING,
        SessionSubscriptionStatus::PAUSED,
        SessionSubscriptionStatus::SUSPENDED,
        SessionSubscriptionStatus::CANCELLED,
    ];

    foreach ($nonPausable as $status) {
        expect($status->canPause())->toBeFalse("Expected {$status->value} to NOT be pausable");
    }
});

test('canResume returns true only for PAUSED', function () {
    expect(SessionSubscriptionStatus::PAUSED->canResume())->toBeTrue();

    $nonResumable = [
        SessionSubscriptionStatus::PENDING,
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::SUSPENDED,
        SessionSubscriptionStatus::CANCELLED,
    ];

    foreach ($nonResumable as $status) {
        expect($status->canResume())->toBeFalse("Expected {$status->value} to NOT be resumable");
    }
});

test('canCancel returns true for PENDING, ACTIVE, PAUSED, SUSPENDED', function () {
    $cancellable = [
        SessionSubscriptionStatus::PENDING,
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::PAUSED,
        SessionSubscriptionStatus::SUSPENDED,
    ];

    foreach ($cancellable as $status) {
        expect($status->canCancel())->toBeTrue("Expected {$status->value} to be cancellable");
    }

    expect(SessionSubscriptionStatus::CANCELLED->canCancel())->toBeFalse();
});

test('canRenew returns true for ACTIVE, PAUSED, SUSPENDED', function () {
    $renewable = [
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::PAUSED,
        SessionSubscriptionStatus::SUSPENDED,
    ];

    foreach ($renewable as $status) {
        expect($status->canRenew())->toBeTrue("Expected {$status->value} to be renewable");
    }

    $nonRenewable = [
        SessionSubscriptionStatus::PENDING,
        SessionSubscriptionStatus::CANCELLED,
    ];

    foreach ($nonRenewable as $status) {
        expect($status->canRenew())->toBeFalse("Expected {$status->value} to NOT be renewable");
    }
});

test('isTerminal returns true only for CANCELLED', function () {
    expect(SessionSubscriptionStatus::CANCELLED->isTerminal())->toBeTrue();

    $nonTerminal = [
        SessionSubscriptionStatus::PENDING,
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::PAUSED,
        SessionSubscriptionStatus::SUSPENDED,
    ];

    foreach ($nonTerminal as $status) {
        expect($status->isTerminal())->toBeFalse("Expected {$status->value} to NOT be terminal");
    }
});

test('validTransitions returns correct transitions for each status', function () {
    expect(SessionSubscriptionStatus::PENDING->validTransitions())->toBe([
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::CANCELLED,
    ]);

    expect(SessionSubscriptionStatus::ACTIVE->validTransitions())->toBe([
        SessionSubscriptionStatus::PAUSED,
        SessionSubscriptionStatus::SUSPENDED,
        SessionSubscriptionStatus::CANCELLED,
    ]);

    expect(SessionSubscriptionStatus::PAUSED->validTransitions())->toBe([
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::CANCELLED,
    ]);

    expect(SessionSubscriptionStatus::SUSPENDED->validTransitions())->toBe([
        SessionSubscriptionStatus::ACTIVE,
        SessionSubscriptionStatus::CANCELLED,
    ]);

    expect(SessionSubscriptionStatus::CANCELLED->validTransitions())->toBe([
        SessionSubscriptionStatus::ACTIVE,
    ]);
});

// ========================================
// SubscriptionPaymentStatus Tests
// ========================================

test('allowsAccess returns true only for PAID', function () {
    expect(SubscriptionPaymentStatus::PAID->allowsAccess())->toBeTrue();
    expect(SubscriptionPaymentStatus::PENDING->allowsAccess())->toBeFalse();
    expect(SubscriptionPaymentStatus::FAILED->allowsAccess())->toBeFalse();
});

test('canRetry returns true for PENDING and FAILED', function () {
    expect(SubscriptionPaymentStatus::PENDING->canRetry())->toBeTrue();
    expect(SubscriptionPaymentStatus::FAILED->canRetry())->toBeTrue();
    expect(SubscriptionPaymentStatus::PAID->canRetry())->toBeFalse();
});

// ========================================
// BillingCycle Tests
// ========================================

test('calculateEndDate adds correct periods', function () {
    $start = Carbon::parse('2026-01-15');

    expect(BillingCycle::MONTHLY->calculateEndDate($start)->toDateString())->toBe('2026-02-15');
    expect(BillingCycle::QUARTERLY->calculateEndDate($start)->toDateString())->toBe('2026-04-15');
    expect(BillingCycle::YEARLY->calculateEndDate($start)->toDateString())->toBe('2027-01-15');
    expect(BillingCycle::LIFETIME->calculateEndDate($start)->year)->toBe(2126);
});

test('supportsAutoRenewal returns false only for LIFETIME', function () {
    expect(BillingCycle::MONTHLY->supportsAutoRenewal())->toBeTrue();
    expect(BillingCycle::QUARTERLY->supportsAutoRenewal())->toBeTrue();
    expect(BillingCycle::YEARLY->supportsAutoRenewal())->toBeTrue();
    expect(BillingCycle::LIFETIME->supportsAutoRenewal())->toBeFalse();
});
