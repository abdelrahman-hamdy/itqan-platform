<?php

use App\Enums\AttendanceStatus;
use App\Services\Traits\AttendanceCalculatorTrait;

/**
 * AttendanceCalculatorTrait — boundary tests for the percentage-based
 * calculateAttendanceStatus() signature.
 *
 * Status decision:
 *   percentage >= fullPercent    → ATTENDED
 *   percentage >= partialPercent → PARTIALLY_ATTENDED
 *   percentage <  partialPercent → ABSENT
 *   firstJoinTime === null       → ABSENT (regardless of minutes)
 *   sessionDuration <= 0         → ABSENT (divide-by-zero guard)
 */
function calcStudent(...$args): string
{
    $obj = new class
    {
        use AttendanceCalculatorTrait;

        public function calc(...$args): string
        {
            return $this->calculateAttendanceStatus(...$args);
        }
    };

    return $obj->calc(...$args);
}

it('returns ATTENDED at exactly the full threshold', function () {
    // 48 / 60 = 80% → exactly full → ATTENDED
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 48,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ATTENDED->value);
});

it('returns PARTIALLY_ATTENDED just below the full threshold', function () {
    // 47 / 60 = 78.3% → below 80%, above 50% → PARTIALLY_ATTENDED
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 47,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::PARTIALLY_ATTENDED->value);
});

it('returns PARTIALLY_ATTENDED at exactly the partial threshold', function () {
    // 30 / 60 = 50% → exactly partial → PARTIALLY_ATTENDED
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 30,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::PARTIALLY_ATTENDED->value);
});

it('returns ABSENT just below the partial threshold', function () {
    // 29 / 60 = 48.3% → below 50% → ABSENT
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 29,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ABSENT->value);
});

it('returns ABSENT when firstJoinTime is null regardless of minutes', function () {
    // Even with 100% "duration" minutes, null join means absent.
    expect(calcStudent(
        firstJoinTime: null,
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 60,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ABSENT->value);
});

it('returns ABSENT when session duration is zero (divide-by-zero guard)', function () {
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 0,
        actualAttendanceMinutes: 10,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ABSENT->value);
});

it('returns ATTENDED when attended minutes exceed scheduled duration', function () {
    // 70 / 60 = 116.6% — can happen with early-join-then-stay in jobs that
    // don't cap. Still maps to ATTENDED; no clamping is applied in the trait.
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(70),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 70,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ATTENDED->value);
});

it('returns ABSENT at zero attended minutes even when joined', function () {
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 0,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ABSENT->value);
});

it('supports custom thresholds (teacher-style 90/50)', function () {
    // 54 / 60 = 90% — exactly the teacher full threshold
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 54,
        fullPercent: 90.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ATTENDED->value);

    // 53 / 60 = 88.3% — just below 90%, above 50%
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 53,
        fullPercent: 90.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::PARTIALLY_ATTENDED->value);
});

it('supports fractional percentages near the full boundary', function () {
    // Session 90 min, 72 / 90 = exactly 80% → ATTENDED
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(90),
        sessionDurationMinutes: 90,
        actualAttendanceMinutes: 72,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::ATTENDED->value);

    // 71 / 90 = 78.8% → PARTIALLY_ATTENDED
    expect(calcStudent(
        firstJoinTime: now()->subMinutes(90),
        sessionDurationMinutes: 90,
        actualAttendanceMinutes: 71,
        fullPercent: 80.0,
        partialPercent: 50.0,
    ))->toBe(AttendanceStatus::PARTIALLY_ATTENDED->value);
});

it('calculates the same way as calculateTeacherAttendanceStatus', function () {
    // Both helpers share the same underlying percentage logic; they should
    // return identical values for identical inputs.
    $student = calcStudent(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 45,
        fullPercent: 90.0,
        partialPercent: 50.0,
    );

    $teacherObj = new class
    {
        use AttendanceCalculatorTrait;

        public function calc(...$args): string
        {
            return $this->calculateTeacherAttendanceStatus(...$args);
        }
    };

    $teacher = $teacherObj->calc(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 45,
        fullPercent: 90.0,
        partialPercent: 50.0,
    );

    expect($student)->toBe($teacher);
    expect($student)->toBe(AttendanceStatus::PARTIALLY_ATTENDED->value);
});
