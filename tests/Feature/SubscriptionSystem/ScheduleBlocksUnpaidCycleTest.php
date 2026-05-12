<?php

declare(strict_types=1);

use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\SessionManagementService;
use Carbon\Carbon;

/**
 * Sub #892 incident regression — bulk-scheduling sessions on an unpaid
 * active cycle is the path that minted 16 sessions on a never-paid cycle
 * 16 minutes after the renew-gate fix landed. The renew-gate + cron
 * paid-only filter close two paths; this test pins the third gate (the
 * scheduling path) so we don't regress again.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'sub892gate-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);
    $this->actingAs($this->student);
});

function makeIndividualCircleWith(SubscriptionPaymentStatus $cyclePaymentStatus, $academy, $teacher, $student): array
{
    $sub = QuranSubscription::factory()
        ->forStudent($student)
        ->forTeacher($teacher)
        ->active()
        ->create([
            'academy_id' => $academy->id,
            'total_sessions' => 16,
            'sessions_used' => 0,
            'sessions_remaining' => 16,
            // Set sub-level payment_status to PAID so the pre-existing
            // isSchedulable() validator passes. The new cycle-level gate
            // is the one we're actually testing.
            'payment_status' => SubscriptionPaymentStatus::PAID,
        ]);

    $cycle = SubscriptionCycle::create([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'billing_cycle' => 'monthly',
        'starts_at' => Carbon::now()->subDay(),
        'ends_at' => Carbon::now()->addMonth(),
        'total_sessions' => 16,
        'sessions_used' => 0,
        'sessions_completed' => 0,
        'sessions_missed' => 0,
        'total_price' => 200,
        'final_price' => 200,
        'currency' => 'SAR',
        'payment_status' => $cyclePaymentStatus === SubscriptionPaymentStatus::PAID
            ? SubscriptionCycle::PAYMENT_PAID
            : SubscriptionCycle::PAYMENT_PENDING,
    ]);

    $sub->update(['current_cycle_id' => $cycle->id]);

    $circle = QuranIndividualCircle::factory()->create([
        'academy_id' => $academy->id,
        'quran_teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'subscription_id' => $sub->id,
        'total_sessions' => 16,
        'default_duration_minutes' => 60,
    ]);

    return [$sub, $circle, $cycle];
}

it('refuses bulk-schedule when current cycle payment_status is PENDING', function () {
    [, $circle] = makeIndividualCircleWith(SubscriptionPaymentStatus::PENDING, $this->academy, $this->teacher, $this->student);

    $service = app(SessionManagementService::class);

    expect(fn () => $service->createIndividualCircleSchedule($circle, [
        'session_count' => 4,
        'schedule_days' => ['monday', 'wednesday'],
        'schedule_time' => '14:00',
        'schedule_start_date' => Carbon::now()->addDays(2)->toDateString(),
    ]))->toThrow(\Exception::class, __('scheduling.errors.current_cycle_unpaid'));

    // No sessions should have been created
    expect($circle->sessions()->count())->toBe(0);
});

it('allows bulk-schedule when current cycle payment_status is PAID', function () {
    [, $circle] = makeIndividualCircleWith(SubscriptionPaymentStatus::PAID, $this->academy, $this->teacher, $this->student);

    $service = app(SessionManagementService::class);

    // Start on the next Monday so the schedule_days filter has matches in
    // the generated window — generateSessionDates only emits dates whose
    // weekday is in schedule_days.
    $startDate = Carbon::now()->next('monday')->toDateString();

    $result = $service->createIndividualCircleSchedule($circle, [
        'session_count' => 2,
        'schedule_days' => ['monday', 'wednesday'],
        'schedule_time' => '14:00',
        'schedule_start_date' => $startDate,
    ]);

    expect($result->created)->toBeGreaterThan(0, 'expected at least one session, failures: '.json_encode($result->failures));
});

it('isCurrentCyclePaymentPending() reflects the hybrid state', function () {
    [$subPaid] = makeIndividualCircleWith(SubscriptionPaymentStatus::PAID, $this->academy, $this->teacher, $this->student);
    [$subUnpaid] = makeIndividualCircleWith(SubscriptionPaymentStatus::PENDING, $this->academy, $this->teacher, $this->student);

    expect($subPaid->refresh()->isCurrentCyclePaymentPending())->toBeFalse();
    expect($subUnpaid->refresh()->isCurrentCyclePaymentPending())->toBeTrue();
});
