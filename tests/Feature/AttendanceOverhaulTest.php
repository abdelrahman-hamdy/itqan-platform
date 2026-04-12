<?php

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\AcademySettings;
use App\Models\QuranSession;
use App\Services\SessionCountingService;
use App\Services\SessionSchedulerService;
use App\Services\SessionSettingsService;
use App\Services\Traits\AttendanceCalculatorTrait;

/**
 * Attendance Overhaul Tests
 *
 * Verifies the core behaviors of the refactored attendance system:
 * - Session status enum (ABSENT/FORGIVEN removed)
 * - Auto-completion of sessions
 * - Teacher attendance calculation with dynamic thresholds
 * - Counting flags (counts_for_teacher, counts_for_subscription)
 * - Subscription counting
 */
beforeEach(function () {
    $this->academy = createAcademy();
    setTenantContext($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->student = createStudent($this->academy);
});

// ============================================
// A. Session Status Enum
// ============================================

it('does not have ABSENT or FORGIVEN as valid session statuses', function () {
    $values = SessionStatus::values();

    expect($values)->not->toContain('absent');
    expect($values)->not->toContain('forgiven');
    expect($values)->toContain('completed');
    expect($values)->toContain('cancelled');
    expect($values)->toContain('scheduled');
});

it('has PARTIALLY_ATTENDED as a valid attendance status', function () {
    $values = AttendanceStatus::values();

    expect($values)->toContain('partially_attended');
    expect($values)->toContain('attended');
    expect($values)->toContain('absent');
    expect($values)->toContain('late');
    expect($values)->toContain('left');
});

it('marks completed sessions as final', function () {
    expect(SessionStatus::COMPLETED->isFinal())->toBeTrue();
    expect(SessionStatus::CANCELLED->isFinal())->toBeTrue();
    expect(SessionStatus::SCHEDULED->isFinal())->toBeFalse();
    expect(SessionStatus::ONGOING->isFinal())->toBeFalse();
});

// ============================================
// B. Auto-Completion
// ============================================

it('auto-completes scheduled sessions when time passes', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::SCHEDULED,
        'scheduled_at' => now()->subHours(2),
        'duration_minutes' => 60,
    ]);

    $schedulerService = app(SessionSchedulerService::class);

    expect($schedulerService->shouldAutoComplete($session))->toBeTrue();
});

it('auto-completes ready sessions when time passes', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::READY,
        'scheduled_at' => now()->subHours(2),
        'duration_minutes' => 60,
    ]);

    $schedulerService = app(SessionSchedulerService::class);

    expect($schedulerService->shouldAutoComplete($session))->toBeTrue();
});

it('does not auto-complete sessions still within their time window', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::ONGOING,
        'scheduled_at' => now()->subMinutes(30),
        'duration_minutes' => 60,
    ]);

    $schedulerService = app(SessionSchedulerService::class);

    expect($schedulerService->shouldAutoComplete($session))->toBeFalse();
});

it('shouldTransitionToAbsent always returns false (deprecated)', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::READY,
        'scheduled_at' => now()->subHours(2),
        'duration_minutes' => 60,
    ]);

    $schedulerService = app(SessionSchedulerService::class);

    expect($schedulerService->shouldTransitionToAbsent($session))->toBeFalse();
});

// ============================================
// C. Teacher Attendance Calculation
// ============================================

it('calculates teacher as ATTENDED when above full threshold', function () {
    $calculator = new class
    {
        use AttendanceCalculatorTrait;

        public function calc(...$args): string
        {
            return $this->calculateTeacherAttendanceStatus(...$args);
        }
    };

    $result = $calculator->calc(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 55, // 91.7% > 90%
        fullPercent: 90.0,
        partialPercent: 50.0,
    );

    expect($result)->toBe(AttendanceStatus::ATTENDED->value);
});

it('calculates teacher as PARTIALLY_ATTENDED when between thresholds', function () {
    $calculator = new class
    {
        use AttendanceCalculatorTrait;

        public function calc(...$args): string
        {
            return $this->calculateTeacherAttendanceStatus(...$args);
        }
    };

    $result = $calculator->calc(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 40, // 66.7% — between 50% and 90%
        fullPercent: 90.0,
        partialPercent: 50.0,
    );

    expect($result)->toBe(AttendanceStatus::PARTIALLY_ATTENDED->value);
});

it('calculates teacher as ABSENT when below partial threshold', function () {
    $calculator = new class
    {
        use AttendanceCalculatorTrait;

        public function calc(...$args): string
        {
            return $this->calculateTeacherAttendanceStatus(...$args);
        }
    };

    $result = $calculator->calc(
        firstJoinTime: now()->subMinutes(60),
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 20, // 33.3% < 50%
        fullPercent: 90.0,
        partialPercent: 50.0,
    );

    expect($result)->toBe(AttendanceStatus::ABSENT->value);
});

it('calculates teacher as ABSENT when they never joined', function () {
    $calculator = new class
    {
        use AttendanceCalculatorTrait;

        public function calc(...$args): string
        {
            return $this->calculateTeacherAttendanceStatus(...$args);
        }
    };

    $result = $calculator->calc(
        firstJoinTime: null,
        sessionDurationMinutes: 60,
        actualAttendanceMinutes: 0,
    );

    expect($result)->toBe(AttendanceStatus::ABSENT->value);
});

// ============================================
// D. Dynamic Thresholds from Academy Settings
// ============================================

it('reads teacher thresholds from academy settings', function () {
    $settings = AcademySettings::factory()->create([
        'academy_id' => $this->academy->id,
        'teacher_full_attendance_percent' => 85.00,
        'teacher_partial_attendance_percent' => 40.00,
    ]);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
    ]);

    $settingsService = app(SessionSettingsService::class);

    expect($settingsService->getTeacherFullAttendancePercent($session))->toBe(85.0);
    expect($settingsService->getTeacherPartialAttendancePercent($session))->toBe(40.0);
});

it('falls back to config defaults when academy settings not set', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
    ]);

    $settingsService = app(SessionSettingsService::class);

    // Defaults from config or hardcoded
    expect($settingsService->getTeacherFullAttendancePercent($session))->toBeGreaterThanOrEqual(80.0);
    expect($settingsService->getStudentPartialAttendancePercent($session))->toBeGreaterThanOrEqual(30.0);
});

// ============================================
// E. Counting Flags
// ============================================

it('sets counts_for_teacher on completed sessions', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::COMPLETED,
        'counts_for_teacher' => true,
        'teacher_attendance_status' => AttendanceStatus::ATTENDED->value,
    ]);

    expect($session->counts_for_teacher)->toBeTrue();
    expect($session->teacher_attendance_status)->toBe('attended');
});

it('allows admin to override counts_for_teacher', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::COMPLETED,
        'counts_for_teacher' => true,
    ]);

    $service = app(SessionCountingService::class);
    $service->setCountsForTeacher($session, false, $this->teacher->id);

    $session->refresh();

    expect($session->counts_for_teacher)->toBeFalse();
    expect($session->counts_for_teacher_set_by)->toBe($this->teacher->id);
    expect($session->counts_for_teacher_set_at)->not->toBeNull();
});

// ============================================
// F. Subscription Counting Integration
// ============================================

it('counts completed sessions towards subscription', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::COMPLETED,
    ]);

    expect($session->countsTowardsSubscription())->toBeTrue();
});

it('does not count cancelled sessions towards subscription', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::CANCELLED,
    ]);

    expect($session->countsTowardsSubscription())->toBeFalse();
});

// ============================================
// G. Earnings Eligibility
// ============================================

it('uses counts_for_teacher flag for earnings eligibility', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::COMPLETED,
        'counts_for_teacher' => false,
    ]);

    $earningsService = app(\App\Services\EarningsCalculationService::class);

    // Use reflection to test protected method
    $method = new ReflectionMethod($earningsService, 'isEligibleForEarnings');
    $method->setAccessible(true);

    expect($method->invoke($earningsService, $session))->toBeFalse();
});

it('allows earnings when counts_for_teacher is true', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::COMPLETED,
        'counts_for_teacher' => true,
        'session_type' => 'individual',
    ]);

    $earningsService = app(\App\Services\EarningsCalculationService::class);

    $method = new ReflectionMethod($earningsService, 'isEligibleForEarnings');
    $method->setAccessible(true);

    expect($method->invoke($earningsService, $session))->toBeTrue();
});
