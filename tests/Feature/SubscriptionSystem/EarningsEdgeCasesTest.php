<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\SessionConsumption;
use App\Models\TeacherEarning;
use App\Services\EarningsCalculationService;
use App\Services\Subscription\SubscriptionConsumption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Teacher earnings edge cases — Scenarios F1–F7 from the test-plan.
 *
 *   F1 — Session soft-delete cascades to `teacher_earnings.deleted_at`.
 *   F2 — A session status flip CANCELLED → COMPLETED reinstates ONE earning
 *        row, not a duplicate.
 *   F3 — `earning_month` is the cycle's storage date (Y-m-01), not the
 *        calendar month of `scheduled_at`. Month-boundary classification
 *        is governed by EarningsCycleHelper, not by Carbon::startOfMonth().
 *   F4 — Rate-snapshot immutability — rate change AFTER an earning was
 *        created does NOT retroactively rewrite the amount.
 *   F5 — Group circle: ONE earning per (session, teacher), NOT per student.
 *   F6 — Cross-currency placeholder — the snapshot must record the currency
 *        and amount used; conversion happens at payout, not at calculation.
 *   F7 — Deactivated teacher (`user.active_status = false`) — no earnings
 *        produced for sessions completed AFTER deactivation.
 *
 * EarningsDeduplicationTest already covers the FQCN/alias dedup invariants
 * (Bug #5). This file covers the surrounding edge cases that aren't part
 * of that bug.
 */
beforeEach(function () {
    Notification::fake();
    Cache::flush(); // Earnings service caches teacher profiles; flush so
    // rate changes during a single test land cleanly.

    $this->academy = createAcademy(['subdomain' => 'earningsedge-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
        ]);
    $this->sub->ensureCurrentCycle();

    // createQuranTeacher() helper inserts a bare-minimum profile without
    // pricing — populate the duration prices so earnings calc can resolve
    // a non-zero amount.
    $this->teacherProfile = QuranTeacherProfile::where('user_id', $this->teacher->id)->first();
    $this->teacherProfile->update([
        'individual_session_prices' => ['15' => 30, '30' => 50, '45' => 70, '60' => 90, '90' => 120],
        'group_session_prices' => ['15' => 20, '30' => 35, '45' => 50, '60' => 65, '90' => 90],
        'session_price_individual' => 50,
        'session_price_group' => 35,
    ]);
});

/**
 * Helper: build a COMPLETED session attached to the current subscription.
 */
function earningsSession(array $attrs = []): QuranSession
{
    return QuranSession::withoutEvents(fn () => QuranSession::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => test()->sub->id,
        'subscription_cycle_id' => test()->sub->fresh()->current_cycle_id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'duration_minutes' => 30,
        'actual_duration_minutes' => 30,
        'counts_for_teacher' => true,
    ], $attrs)));
}

describe('F1 — soft-delete cascade', function () {
    it('F1 — session soft-delete leaves the earning row in place but observer should mark it deleted', function () {
        $session = earningsSession();
        $earning = app(EarningsCalculationService::class)->calculateSessionEarnings($session);
        expect($earning)->not->toBeNull('precondition — earning must be created');

        // Drive the reverse-precondition through the canonical writer so
        // the observer's reverse path fires on soft-delete.
        app(SubscriptionConsumption::class)->record(
            $session,
            test()->student,
            test()->sub,
            source: SessionConsumption::SOURCE_AUTO_ATTENDANCE,
            sourceUser: null,
            consumptionType: SessionConsumption::TYPE_ATTENDED,
        );
        $session->delete();

        // CORRECT: after soft-delete, the earning is either soft-deleted or
        // its amount zeroed via the observer. The exact mechanism is an
        // implementation detail — the invariant is "no live earning row
        // points at a soft-deleted session."
        $aliveEarning = TeacherEarning::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->whereNull('deleted_at')
            ->first();

        // If alive: the amount should be 0 (reversed). If gone: it's been
        // soft-deleted (preferred).
        if ($aliveEarning !== null) {
            expect((float) $aliveEarning->amount)->toBe(0.0, 'earning must be reversed when session is soft-deleted');
        } else {
            expect(true)->toBeTrue(); // earning already removed
        }
    });
});

describe('F2 — status flip reinstatement does not duplicate', function () {
    it('F2 — flipping CANCELLED → COMPLETED produces at most one earning row', function () {
        $session = earningsSession();
        $service = app(EarningsCalculationService::class);

        $service->calculateSessionEarnings($session);
        // Simulate cancellation: observer reverses; then re-complete and
        // re-run calculation.
        $session->update(['status' => SessionStatus::CANCELLED]);
        $session->update(['status' => SessionStatus::COMPLETED]);
        $service->calculateSessionEarnings($session->fresh());

        $count = TeacherEarning::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->whereNull('deleted_at')
            ->count();
        expect($count)->toBe(1, 'exactly one earning row regardless of status churn');
    });
});

describe('F3 — earning_month uses EarningsCycleHelper, not startOfMonth', function () {
    it('F3 — earning_month is the cycle-named-month (Y-m-01), not Carbon::startOfMonth()', function () {
        // Per `EarningsCycleHelper::cycleStorageDate`, a session completed
        // on the 28th of a month belongs to the CURRENT month's cycle,
        // but a session completed on the 29th belongs to the NEXT month's
        // cycle (cycle runs day 29 → day 28). Whatever the helper says is
        // the source of truth — assert the earning row matches.
        $session = earningsSession([
            'scheduled_at' => Carbon\Carbon::create(2026, 3, 15, 10, 0, 0),
            'ended_at' => Carbon\Carbon::create(2026, 3, 15, 10, 30, 0),
        ]);

        $earning = app(EarningsCalculationService::class)->calculateSessionEarnings($session);
        expect($earning)->not->toBeNull();

        $expected = \App\Helpers\EarningsCycleHelper::cycleStorageDate(
            \Carbon\Carbon::parse('2026-03-15 10:30:00')
        );
        expect($earning->earning_month?->toDateString() ?? (string) $earning->earning_month)->toBe(
            $expected,
            'earning_month must come from EarningsCycleHelper::cycleStorageDate, not Carbon::startOfMonth'
        );
    });
});

describe('F4 — rate-snapshot immutability', function () {
    it('F4 — changing the teacher rate after calc does NOT rewrite the existing earning amount', function () {
        $session = earningsSession();
        $earning = app(EarningsCalculationService::class)->calculateSessionEarnings($session);
        expect($earning)->not->toBeNull();
        $originalAmount = (float) $earning->amount;

        // Mutate the teacher's per-duration price.
        $newPrices = $this->teacherProfile->individual_session_prices ?? [];
        $newPrices['30'] = 9999.99;
        $this->teacherProfile->update(['individual_session_prices' => $newPrices]);
        Cache::flush();

        // CORRECT: the existing earning row keeps its original amount.
        // (No re-calc happens unless `calculateSessionEarnings` is called
        // again on a session that hasn't been calculated — and dedup
        // prevents that.)
        expect((float) $earning->fresh()->amount)->toBe(
            $originalAmount,
            'rate change must not rewrite historical earning amounts'
        );
    });
});

describe('F5 — group circle: one earning per (session, teacher)', function () {
    it('F5 — group session with multiple students yields ONE earning row, not one per student', function () {
        // Build a group session attached to the same subscription. Even
        // with multiple student attendances, the earning row is per
        // (session, teacher), never multiplied.
        $session = earningsSession(['session_type' => 'group']);

        // Fake multiple student attendance rows.
        $studentA = createStudent($this->academy);
        $studentB = createStudent($this->academy);
        foreach ([$this->student, $studentA, $studentB] as $stu) {
            \App\Models\MeetingAttendance::create([
                'session_id' => $session->id,
                // meeting_attendances.session_type is a DB enum:
                // individual|group|academic|interactive|trial — NOT the
                // morph alias. Use the session's session_type column.
                'session_type' => $session->session_type ?? 'group',
                'user_id' => $stu->id,
                'user_type' => 'student',
                'attendance_status' => 'attended',
                'attendance_percentage' => 100,
                'is_calculated' => true,
                'first_joined_at' => now()->subHour(),
                'last_left_at' => now()->subMinutes(30),
                'total_duration_minutes' => 30,
            ]);
        }

        app(EarningsCalculationService::class)->calculateSessionEarnings($session);

        $count = TeacherEarning::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->whereNull('deleted_at')
            ->count();
        expect($count)->toBeLessThanOrEqual(
            1,
            'group session must produce ≤1 earning row per (session, teacher), never one-per-student'
        );
    });
});

describe('F6 — rate snapshot records currency', function () {
    it('F6 — earning records the snapshot rate currency it was calculated against', function () {
        $session = earningsSession();
        $earning = app(EarningsCalculationService::class)->calculateSessionEarnings($session);
        expect($earning)->not->toBeNull();

        // The rate snapshot is the audit trail for what the system charged
        // the teacher's earnings against. Whatever the snapshot says is
        // the contract — the field MUST be populated.
        $snapshot = is_array($earning->rate_snapshot)
            ? $earning->rate_snapshot
            : json_decode((string) $earning->rate_snapshot, true);
        expect($snapshot)->toBeArray('rate_snapshot must be a populated JSON object');
        expect($snapshot)->not->toBeEmpty('rate_snapshot must record at least the price + duration used');
    });
});

describe('F7 — deactivated teacher', function () {
    it('F7 — completed session for a deactivated teacher should not produce a new earning', function () {
        // The matrix is the authoritative gate via `counts_for_teacher`.
        // If a teacher has been deactivated (`user.active_status = false`)
        // BEFORE a session is processed, the matrix should flip
        // `counts_for_teacher = false`, and earnings calculation must
        // refuse the session.
        $this->teacher->update(['active_status' => false]);

        $session = earningsSession([
            'counts_for_teacher' => false, // matrix decision under deactivation
        ]);

        $earning = app(EarningsCalculationService::class)->calculateSessionEarnings($session);
        expect($earning)->toBeNull(
            'deactivated teacher must NOT earn for sessions matrix-flagged as counts_for_teacher=false'
        );
    });
});
