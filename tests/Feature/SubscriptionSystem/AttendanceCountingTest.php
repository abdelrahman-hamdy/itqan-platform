<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Jobs\CalculateSessionForAttendance;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;

/**
 * Bugs #6 + #7 — attendance counting matrix.
 *
 * Tests assert the CORRECT expected behavior per the matrix policy at
 * `app/Jobs/CalculateSessionForAttendance.php` lines 263-291:
 *
 *   teacher present → every student counts (no-show penalty included)
 *   teacher absent + student absent → student counts (no-show penalty)
 *   teacher absent + student present → student does NOT count (refund)
 *
 * Bug #6 (admin issue #3) — session 3322: both absent, student CFS=0 in prod.
 *   Per matrix, both-absent should set CFS=1. If the test passes, the prod
 *   state is a LiveKit telemetry gap (dur=0 from missing webhook), not a
 *   matrix code bug. Bug #6 is then a false positive at the code level.
 *
 * Bug #7 (admin issue #4) — sub 781 sessions 3407,3408: student=absent, CFS=1.
 *   Per matrix, when teacher attended that's the intended NO-SHOW PENALTY.
 *   Test confirms CFS=1 in this scenario is correct behavior, making Bug #7
 *   a false positive (admin misread the rule as a bug).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'attendance-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
            'payment_status' => SubscriptionPaymentStatus::PAID,
        ]);
    $this->sub->ensureCurrentCycle();
});

/** Build a session + teacher/student attendance with the given attendance shape. */
function attendanceFixture(array $teacherAttend, array $studentAttend, int $durationMin = 30): QuranSession
{
    $sub = test()->sub;
    $session = QuranSession::factory()->create([
        'academy_id' => $sub->academy_id,
        'student_id' => $sub->student_id,
        'quran_teacher_id' => $sub->quran_teacher_id,
        'quran_subscription_id' => $sub->id,
        'subscription_cycle_id' => $sub->fresh()->current_cycle_id,
        'scheduled_at' => now()->subMinutes(60),
        'duration_minutes' => $durationMin,
        'status' => SessionStatus::COMPLETED,
    ]);

    // Teacher attendance row (raw insert to set the exact shape we want).
    DB::table('meeting_attendances')->insert(array_merge([
        'academy_id' => $sub->academy_id,
        'session_id' => $session->id,
        'user_id' => $sub->quran_teacher_id,
        'user_type' => 'teacher',
        'session_type' => 'individual',
        'is_calculated' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], $teacherAttend));

    DB::table('meeting_attendances')->insert(array_merge([
        'academy_id' => $sub->academy_id,
        'session_id' => $session->id,
        'user_id' => $sub->student_id,
        'user_type' => 'student',
        'session_type' => 'individual',
        'is_calculated' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], $studentAttend));

    return $session->fresh();
}

describe('Bug #7 — matrix no-show penalty (teacher present + student absent)', function () {
    it('B7-1 — teacher attended + student absent: student counts (no-show penalty)', function () {
        $session = attendanceFixture(
            teacherAttend: [
                'first_join_time' => now()->subMinutes(60),
                'last_leave_time' => now()->subMinutes(30),
                'total_duration_minutes' => 30,
                'attendance_percentage' => 100.00,
                'attendance_status' => 'attended',
            ],
            studentAttend: [
                'first_join_time' => null,
                'total_duration_minutes' => 0,
                'attendance_percentage' => 0.00,
                'attendance_status' => 'absent',
            ],
        );

        // Fire the matrix
        (new CalculateSessionForAttendance($session->id, QuranSession::class))->handle();

        $studentRow = MeetingAttendance::where('session_id', $session->id)->where('user_type', 'student')->first();
        // Per matrix: teacher present → student counts (no-show penalty)
        expect($studentRow->counts_for_subscription)->toBe(true, sprintf(
            'expected CFS=true (no-show penalty), got %s',
            var_export($studentRow->counts_for_subscription, true)
        ));
        expect($studentRow->attendance_status->value)->toBe('absent');
    });

    it('B7-2 — teacher attended + student attended: student counts (normal)', function () {
        $session = attendanceFixture(
            teacherAttend: [
                'first_join_time' => now()->subMinutes(60),
                'total_duration_minutes' => 30,
                'attendance_percentage' => 100.00,
                'attendance_status' => 'attended',
            ],
            studentAttend: [
                'first_join_time' => now()->subMinutes(60),
                'total_duration_minutes' => 30,
                'attendance_percentage' => 100.00,
                'attendance_status' => 'attended',
            ],
        );

        (new CalculateSessionForAttendance($session->id, QuranSession::class))->handle();

        $studentRow = MeetingAttendance::where('session_id', $session->id)->where('user_type', 'student')->first();
        expect($studentRow->counts_for_subscription)->toBe(true);
    });
});

describe('Bug #6 — both-absent scenario (LiveKit telemetry gap)', function () {
    it('B6-1 — teacher absent + student absent: student counts (no-show, no refund)', function () {
        $session = attendanceFixture(
            teacherAttend: [
                'first_join_time' => null,
                'total_duration_minutes' => 0,
                'attendance_percentage' => 0.00,
                'attendance_status' => 'absent',
            ],
            studentAttend: [
                'first_join_time' => null,
                'total_duration_minutes' => 0,
                'attendance_percentage' => 0.00,
                'attendance_status' => 'absent',
            ],
        );

        (new CalculateSessionForAttendance($session->id, QuranSession::class))->handle();

        $studentRow = MeetingAttendance::where('session_id', $session->id)->where('user_type', 'student')->first();
        // Per matrix: teacher absent + student absent → student counts
        // (no-show penalty against the student even when teacher didn't show)
        expect($studentRow->counts_for_subscription)->toBe(true);
    });

    it('B6-2 — teacher absent + student present: student does NOT count (refund)', function () {
        $session = attendanceFixture(
            teacherAttend: [
                'first_join_time' => null,
                'total_duration_minutes' => 0,
                'attendance_percentage' => 0.00,
                'attendance_status' => 'absent',
            ],
            studentAttend: [
                'first_join_time' => now()->subMinutes(60),
                'total_duration_minutes' => 30,
                'attendance_percentage' => 100.00,
                'attendance_status' => 'attended',
            ],
        );

        (new CalculateSessionForAttendance($session->id, QuranSession::class))->handle();

        $studentRow = MeetingAttendance::where('session_id', $session->id)->where('user_type', 'student')->first();
        // Per matrix: teacher absent + student present → student refunded
        expect($studentRow->counts_for_subscription)->toBe(false);
    });
});
