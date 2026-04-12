<?php

use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionForAttendance;
use App\Models\AcademySettings;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;

/**
 * Attendance counting matrix tests.
 *
 * Covers the 6 rows of the matrix:
 *
 * | Teacher %        | Student %        | Earnings | Subscription |
 * |------------------|------------------|----------|--------------|
 * | full (≥90%)      | present (≥50%)   | YES      | YES          |
 * | full (≥90%)      | absent  (<50%)   | YES      | YES          |
 * | partial (50-89%) | present          | NO       | YES          |
 * | partial (50-89%) | absent           | NO       | YES          |
 * | absent  (<50%)   | present          | NO       | NO           |
 * | absent  (<50%)   | absent           | NO       | YES          |
 *
 * counts_for_teacher      = teacherPct >= teacher_full_attendance_percent
 * counts_for_subscription = teacherPct >= teacher_partial_attendance_percent
 *                           OR studentPct < student_partial_attendance_percent
 *
 * NOTE: These tests focus on the matrix pass in
 * CalculateSessionForAttendance::calculateTeacherAttendanceAndSetFlags() and
 * its interaction with counts_for_subscription / counts_for_teacher. They do
 * NOT run the full subscription decrement chain because that path requires
 * more fixture setup than is appropriate for a pure matrix test.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    setTenantContext($this->academy);

    // Ensure deterministic thresholds — don't depend on env defaults
    AcademySettings::firstOrCreate(
        ['academy_id' => $this->academy->id],
        [
            'default_attendance_threshold_percentage' => 80.00, // student_full
            'student_minimum_presence_percent' => 50.00,         // student_partial
            'teacher_full_attendance_percent' => 90.00,
            'teacher_partial_attendance_percent' => 50.00,
        ]
    );

    $this->teacher = createQuranTeacher($this->academy);
    $this->student = createStudent($this->academy);

    // Enable the matrix
    config(['business.attendance.use_matrix_counting' => true]);
});

/**
 * Build a completed QuranSession with teacher + student attendance rows at
 * the specified percentages. Returns [$session, $teacherAttendance, $studentAttendance].
 */
function buildMatrixScenario(
    $academy,
    $teacher,
    $student,
    float $teacherPercent,
    float $studentPercent,
    int $durationMinutes = 60,
): array {
    $session = QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'quran_teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => now()->subHours(2),
        'duration_minutes' => $durationMinutes,
        'session_type' => 'individual',
        'counts_for_teacher' => null,
        'counts_for_teacher_set_by' => null,
        'subscription_counted' => false,
    ]);

    $teacherMinutes = (int) round(($teacherPercent / 100) * $durationMinutes);
    $studentMinutes = (int) round(($studentPercent / 100) * $durationMinutes);

    $teacherAttendance = MeetingAttendance::create([
        'session_id' => $session->id,
        'user_id' => $teacher->id,
        'user_type' => 'teacher',
        'session_type' => 'individual',
        'first_join_time' => $teacherPercent > 0 ? now()->subHours(2) : null,
        'total_duration_minutes' => $teacherMinutes,
        'display_duration_minutes' => $teacherMinutes,
        'attendance_percentage' => $teacherPercent,
        'session_duration_minutes' => $durationMinutes,
        'is_calculated' => true,
        'counts_for_subscription' => null,
        'counts_for_subscription_set_by' => null,
    ]);

    $studentAttendance = MeetingAttendance::create([
        'session_id' => $session->id,
        'user_id' => $student->id,
        'user_type' => 'student',
        'session_type' => 'individual',
        'first_join_time' => $studentPercent > 0 ? now()->subHours(2) : null,
        'total_duration_minutes' => $studentMinutes,
        'display_duration_minutes' => $studentMinutes,
        'attendance_percentage' => $studentPercent,
        'session_duration_minutes' => $durationMinutes,
        'is_calculated' => true,
        'counts_for_subscription' => null,
        'counts_for_subscription_set_by' => null,
    ]);

    return [$session, $teacherAttendance, $studentAttendance];
}

/**
 * Invoke only the teacher-flag-and-matrix pass using reflection so we can test
 * the decision logic in isolation without involving subscriptions/earnings.
 */
function runMatrixPass(QuranSession $session): void
{
    $job = new CalculateSessionForAttendance($session->id, QuranSession::class);

    $reflection = new \ReflectionClass($job);
    $method = $reflection->getMethod('calculateTeacherAttendanceAndSetFlags');
    $method->setAccessible(true);
    $method->invoke($job, $session->fresh());
}

it('matrix row 1: teacher full + student present → earnings YES, subscription YES', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 95.0,
        studentPercent: 90.0,
    );

    runMatrixPass($session);

    expect($session->fresh()->counts_for_teacher)->toBeTrue()
        ->and($studentAttendance->fresh()->counts_for_subscription)->toBeTrue();
});

it('matrix row 2: teacher full + student absent → earnings YES, subscription YES', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 95.0,
        studentPercent: 0.0,
    );

    runMatrixPass($session);

    expect($session->fresh()->counts_for_teacher)->toBeTrue()
        ->and($studentAttendance->fresh()->counts_for_subscription)->toBeTrue();
});

it('matrix row 3: teacher partial + student present → earnings NO, subscription YES', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 60.0, // between partial 50 and full 90
        studentPercent: 90.0,
    );

    runMatrixPass($session);

    expect($session->fresh()->counts_for_teacher)->toBeFalse()
        ->and($studentAttendance->fresh()->counts_for_subscription)->toBeTrue();
});

it('matrix row 4: teacher partial + student absent → earnings NO, subscription YES', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 60.0,
        studentPercent: 0.0,
    );

    runMatrixPass($session);

    expect($session->fresh()->counts_for_teacher)->toBeFalse()
        ->and($studentAttendance->fresh()->counts_for_subscription)->toBeTrue();
});

it('matrix row 5: teacher absent + student present → earnings NO, subscription NO (student refunded)', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 10.0, // below partial 50
        studentPercent: 90.0,
    );

    runMatrixPass($session);

    expect($session->fresh()->counts_for_teacher)->toBeFalse()
        ->and($studentAttendance->fresh()->counts_for_subscription)->toBeFalse();
});

it('matrix row 6: teacher absent + student absent → earnings NO, subscription YES (no-show penalty)', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 0.0,
        studentPercent: 0.0,
    );

    runMatrixPass($session);

    expect($session->fresh()->counts_for_teacher)->toBeFalse()
        ->and($studentAttendance->fresh()->counts_for_subscription)->toBeTrue();
});

it('respects admin override on counts_for_subscription (whereNull guard)', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 95.0,
        studentPercent: 90.0,
    );

    // Admin pre-sets counts_for_subscription to false
    $studentAttendance->update([
        'counts_for_subscription' => false,
        'counts_for_subscription_set_by' => 1,
        'counts_for_subscription_set_at' => now(),
    ]);

    runMatrixPass($session);

    // Admin override must be preserved — matrix skips this row entirely
    expect($studentAttendance->fresh()->counts_for_subscription)->toBeFalse()
        ->and($studentAttendance->fresh()->counts_for_subscription_set_by)->toBe(1);
});

it('respects admin override on counts_for_teacher', function () {
    [$session] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 10.0, // would normally be "no earnings"
        studentPercent: 90.0,
    );

    // Admin pre-sets counts_for_teacher to true
    $session->update([
        'counts_for_teacher' => true,
        'counts_for_teacher_set_by' => 1,
        'counts_for_teacher_set_at' => now(),
    ]);

    runMatrixPass($session);

    // Admin override must be preserved
    expect($session->fresh()->counts_for_teacher)->toBeTrue()
        ->and($session->fresh()->counts_for_teacher_set_by)->toBe(1);
});

it('does not overwrite counts_for_subscription if it is already set (historical safety)', function () {
    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 95.0,
        studentPercent: 90.0,
    );

    // Simulate a pre-deploy row that already has counts_for_subscription set
    // but no admin override. This is the historical-data-safety case.
    $studentAttendance->update(['counts_for_subscription' => false]);

    runMatrixPass($session);

    // The whereNull('counts_for_subscription') guard must keep the existing value
    expect($studentAttendance->fresh()->counts_for_subscription)->toBeFalse();
});

it('feature flag off disables the matrix pass entirely', function () {
    config(['business.attendance.use_matrix_counting' => false]);

    [$session, , $studentAttendance] = buildMatrixScenario(
        $this->academy, $this->teacher, $this->student,
        teacherPercent: 10.0, // would normally make counts_for_subscription=false
        studentPercent: 90.0,
    );

    runMatrixPass($session);

    // Matrix skipped — counts_for_subscription stays NULL (legacy behavior
    // falls back to CountsTowardsSubscription default which defaults to true
    // via the trait's downstream sync).
    expect($studentAttendance->fresh()->counts_for_subscription)->toBeNull();
});
