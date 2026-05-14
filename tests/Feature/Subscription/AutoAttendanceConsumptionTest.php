<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionAuditLog;
use App\Models\SubscriptionCycle;
use App\Support\Subscriptions\AttendanceConsumptionMapper;

/**
 * Tier 3 / G2 — auto-attendance v2 cutover.
 *
 * Verifies that the AttendanceConsumptionMapper produces the documented
 * (attendance status → consumption_type) mapping, and that the v2 path on
 * SubscriptionConsumption::record produces:
 *   - exactly one active session_consumption row per (session, sub)
 *   - the cycle's sessions_used reflects the new row (via reconciler)
 *   - a SubscriptionAuditLog row of action='consumption.record' lands
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
        'session_duration_minutes' => 30,
    ]);

    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'package_id' => $this->package->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions' => 8,
        'sessions_used' => 0,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(27),
        'last_payment_date' => now()->subDay(),
    ]);
    $this->sub->reconciling = true;
    $this->sub->save();
    $this->sub->reconciling = false;

    $this->cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(27),
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
});

it('maps every attendance status to the documented consumption_type bucket', function () {
    expect(AttendanceConsumptionMapper::consumptionTypeFor(AttendanceStatus::ATTENDED))
        ->toBe(SessionConsumption::TYPE_ATTENDED)
        ->and(AttendanceConsumptionMapper::consumptionTypeFor(AttendanceStatus::LATE))
        ->toBe(SessionConsumption::TYPE_LATE)
        ->and(AttendanceConsumptionMapper::consumptionTypeFor(AttendanceStatus::LEFT))
        ->toBe(SessionConsumption::TYPE_LEFT)
        ->and(AttendanceConsumptionMapper::consumptionTypeFor(AttendanceStatus::ABSENT, true))
        ->toBe(SessionConsumption::TYPE_ABSENT_COUNTED)
        ->and(AttendanceConsumptionMapper::consumptionTypeFor(AttendanceStatus::ABSENT, false))
        ->toBeNull();
});

it('records a session_consumption row + bumps cycle counter + audits via v2 service', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycle->id,
        'status' => SessionStatus::COMPLETED,
        'attendance_status' => AttendanceStatus::ATTENDED,
        'duration_minutes' => 30,
        'scheduled_at' => now()->subHour(),
    ]);

    app(\App\Services\Subscription\SubscriptionConsumption::class)->record(
        $session,
        $this->student,
        $this->sub,
        source: SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        sourceUser: null,
        consumptionType: SessionConsumption::TYPE_ATTENDED,
    );

    expect(SessionConsumption::query()
        ->where('session_id', $session->id)
        ->where('subscription_id', $this->sub->id)
        ->whereNull('reversed_at')
        ->count())->toBe(1);

    $this->cycle->refresh();
    $this->sub->refresh();

    expect((int) $this->cycle->sessions_used)->toBe(1)
        ->and((int) $this->sub->sessions_used)->toBe(1);

    expect(SubscriptionAuditLog::query()
        ->where('subscription_id', $this->sub->id)
        ->where('action', 'consumption.record')
        ->count())->toBe(1);
});

it('keeps legacy useSession() path when the v2_consumption_enabled flag is off', function () {
    config(['subscriptions.v2_consumption_enabled' => false]);

    // Confirm the legacy path still increments via useSession.
    $beforeUsed = (int) $this->sub->fresh()->sessions_used;
    $this->sub->useSession($this->cycle->id);
    $afterUsed = (int) $this->sub->fresh()->sessions_used;

    expect($afterUsed - $beforeUsed)->toBe(1);

    // No session_consumption row when the legacy path runs (v2 is off).
    expect(SessionConsumption::query()
        ->where('subscription_id', $this->sub->id)
        ->count())->toBe(0);
});
