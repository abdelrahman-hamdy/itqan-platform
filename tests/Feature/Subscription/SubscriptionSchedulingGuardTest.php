<?php

use App\Enums\BillingCycle;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\SubscriptionException;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\Scheduling\Validators\IndividualCircleValidator;
use App\Services\SessionManagementService;

/**
 * End-to-end tests that validate the scheduling guard wiring:
 *
 *   - The individual-circle validator rejects non-schedulable subscriptions
 *   - The observer-level guard rejects direct QuranSession::create() against
 *     non-schedulable subscriptions
 *   - Grace-period subscriptions pass both gates
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function makeIndividualCircleWithSub(array $subOverrides = []): array
{
    $sub = QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'billing_cycle' => BillingCycle::MONTHLY,
        'subscription_type' => 'individual',
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
        'total_sessions' => 8,
        'sessions_used' => 0,
    ], $subOverrides));

    $circle = QuranIndividualCircle::create([
        'academy_id' => test()->academy->id,
        'quran_teacher_id' => test()->teacher->id,
        'student_id' => test()->student->id,
        'subscription_id' => $sub->id,
        'circle_code' => 'TC-'.uniqid(),
        'name' => 'Test Circle',
        'total_sessions' => 8,
        'sessions_scheduled' => 0,
        'sessions_completed' => 0,
        'sessions_remaining' => 8,
        'is_active' => true,
    ]);

    return [$sub, $circle];
}

test('validator rejects pending subscription with no grace period', function () {
    [$sub, $circle] = makeIndividualCircleWithSub([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'metadata' => null,
    ]);

    $validator = new IndividualCircleValidator($circle, app(SessionManagementService::class));
    $result = $validator->validateDateRange(now(), 4);

    expect($result->isError())->toBeTrue();
});

test('validator accepts pending subscription with active grace period', function () {
    [$sub, $circle] = makeIndividualCircleWithSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(7)->toDateTimeString(),
        ],
    ]);

    $validator = new IndividualCircleValidator($circle, app(SessionManagementService::class));
    $result = $validator->validateDateRange(now(), 2);

    // Grace period makes it pass the subscription_inactive gate.
    // Other validation (date range, sessions remaining) may still produce warnings,
    // but the subscription_inactive error must NOT appear.
    if ($result->isError()) {
        expect($result->getMessage())->not->toContain('subscription_inactive');
    }
});

test('observer guard blocks direct QuranSession::create for non-schedulable subscription', function () {
    [$sub, $circle] = makeIndividualCircleWithSub([
        'status' => SessionSubscriptionStatus::CANCELLED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    expect(fn () => QuranSession::create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'individual_circle_id' => $circle->id,
        'student_id' => $this->student->id,
        'quran_subscription_id' => $sub->id,
        'scheduled_at' => now()->addDays(3),
        'duration_minutes' => 60,
        'session_type' => 'individual',
        'title' => 'Test session',
        'status' => SessionStatus::SCHEDULED,
    ]))->toThrow(SubscriptionException::class);
});

test('observer guard allows session creation when subscription is in grace period', function () {
    [$sub, $circle] = makeIndividualCircleWithSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(7)->toDateTimeString(),
        ],
    ]);

    $session = QuranSession::create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'individual_circle_id' => $circle->id,
        'student_id' => $this->student->id,
        'quran_subscription_id' => $sub->id,
        'scheduled_at' => now()->addDays(3),
        'duration_minutes' => 60,
        'session_type' => 'individual',
        'title' => 'Test session',
        'status' => SessionStatus::SCHEDULED,
    ]);

    expect($session->id)->not->toBeNull();
});
