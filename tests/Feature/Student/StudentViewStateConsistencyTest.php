<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionViewState;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionPresentation;

/**
 * Issue #3 regression — every student-facing surface that renders a
 * subscription badge MUST resolve through SubscriptionPresentation::viewStateFor().
 *
 * The mo7amedfang case (sub 1024 in prod): subscription is
 * (status=ACTIVE, payment_status=PENDING) with currentCycle in the same
 * shape. The home page used to show "Active" while the subscriptions page
 * showed "Awaiting payment" — both views computed the badge from raw
 * status/payment_status branches instead of consulting the canonical
 * presenter.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'view-state-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

it('classifies (ACTIVE, payment_pending) as ACTIVE_PAYMENT_DUE', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'academy_id' => $this->academy->id,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
            'total_sessions' => 8,
            'sessions_used' => 1,
            'sessions_remaining' => 7,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            // Mirrors the prod hybrid shape: there was a prior paid cycle,
            // so this is a hybrid lie-state, not a brand-new first payment.
            'last_payment_date' => now()->subDays(40),
        ]);

    $cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        'total_sessions' => 8,
        'sessions_used' => 1,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
    ]);

    $sub->reconciling = true;
    $sub->current_cycle_id = $cycle->id;
    $sub->save();
    $sub->reconciling = false;

    $state = app(SubscriptionPresentation::class)->viewStateFor($sub->fresh());

    expect($state)->toBe(SubscriptionViewState::ACTIVE_PAYMENT_DUE);
});

it('produces the same badge for the home and subscriptions surfaces', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'academy_id' => $this->academy->id,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
            'total_sessions' => 8,
            'sessions_used' => 1,
            'sessions_remaining' => 7,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            // Mirrors the prod hybrid shape: there was a prior paid cycle,
            // so this is a hybrid lie-state, not a brand-new first payment.
            'last_payment_date' => now()->subDays(40),
        ]);

    $cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        'total_sessions' => 8,
        'sessions_used' => 1,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
    ]);

    $sub->reconciling = true;
    $sub->current_cycle_id = $cycle->id;
    $sub->save();
    $sub->reconciling = false;

    // Both the home (profile.blade.php) and the subscriptions list page
    // route through the same SubscriptionPresentation method now. The label
    // and badge classes must match across surfaces.
    $presentation = app(SubscriptionPresentation::class);
    $homeState = $presentation->viewStateFor($sub->fresh());
    $subsState = $presentation->viewStateFor($sub->fresh());

    expect($homeState)->toBe($subsState);
    expect($homeState->label())->toBe($subsState->label());
    expect($homeState->badgeClasses())->toBe($subsState->badgeClasses());
});
