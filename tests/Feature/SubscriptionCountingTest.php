<?php

use App\Enums\AttendanceStatus;
use App\Enums\BillingCycle;
use App\Enums\LessonStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Events\SessionCompletedEvent;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\MeetingAttendance;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentSessionReport;
use App\Services\LiveKitService;
use App\Services\Session\SessionStatusService;
use App\Services\SessionTransitionService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    setTenantContext($this->academy);
});

/**
 * Helper: Create a Quran individual session wired to a subscription.
 */
function createQuranSessionWithSubscription(array $sessionOverrides = [], array $subscriptionOverrides = []): array
{
    $subscription = QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'billing_cycle' => BillingCycle::MONTHLY,
        'total_sessions' => 10,
        'sessions_used' => 0,
        'sessions_remaining' => 10,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
    ], $subscriptionOverrides));

    $circle = QuranIndividualCircle::factory()->create([
        'academy_id' => test()->academy->id,
        'quran_teacher_id' => test()->teacher->id,
        'student_id' => test()->student->id,
        'subscription_id' => $subscription->id,
        'total_sessions' => 10,
        'sessions_completed' => 0,
        'sessions_remaining' => 10,
    ]);

    $session = QuranSession::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'quran_teacher_id' => test()->teacher->id,
        'student_id' => test()->student->id,
        'individual_circle_id' => $circle->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'subscription_counted' => false,
        'scheduled_at' => now()->subHour(),
        'duration_minutes' => 45,
    ], $sessionOverrides));

    return compact('subscription', 'circle', 'session');
}

/**
 * Helper: Create an Academic individual session wired to a subscription.
 */
function createAcademicSessionWithSubscription(array $sessionOverrides = []): array
{
    $academicTeacher = createAcademicTeacher(test()->academy);
    $profileId = $academicTeacher->academicTeacherProfile->id;
    $subject = AcademicSubject::factory()->create(['academy_id' => test()->academy->id]);
    $gradeLevel = AcademicGradeLevel::factory()->create(['academy_id' => test()->academy->id]);

    $subscription = AcademicSubscription::factory()->create([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'teacher_id' => $profileId,
        'subject_id' => $subject->id,
        'status' => 'active',
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions_scheduled' => 10,
        'total_sessions_completed' => 0,
        'sessions_remaining' => 10,
        'sessions_used' => 0,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
    ]);

    // Create lesson directly — factory has stale 'subject' column
    $lesson = AcademicIndividualLesson::create([
        'academy_id' => test()->academy->id,
        'academic_teacher_id' => $profileId,
        'student_id' => test()->student->id,
        'academic_subscription_id' => $subscription->id,
        'name' => 'Test Lesson',
        'academic_subject_id' => $subject->id,
        'academic_grade_level_id' => $gradeLevel->id,
        'total_sessions' => 10,
        'sessions_scheduled' => 0,
        'sessions_completed' => 0,
        'sessions_remaining' => 10,
        'default_duration_minutes' => 60,
        'status' => LessonStatus::ACTIVE,
        'recording_enabled' => false,
    ]);

    $session = AcademicSession::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'academic_teacher_id' => $profileId,
        'student_id' => test()->student->id,
        'academic_individual_lesson_id' => $lesson->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'subscription_counted' => false,
        'scheduled_at' => now()->subHour(),
        'duration_minutes' => 60,
    ], $sessionOverrides));

    return compact('subscription', 'lesson', 'session', 'academicTeacher');
}

/**
 * Helper: Create a StudentSessionReport with correct academy scoping.
 */
function createReport(string $sessionId, string $studentId, AttendanceStatus $status): StudentSessionReport
{
    return StudentSessionReport::factory()->create([
        'session_id' => $sessionId,
        'student_id' => $studentId,
        'academy_id' => test()->academy->id,
        'teacher_id' => test()->teacher->id,
        'attendance_status' => $status,
    ]);
}

// ─── Test 1: The bug scenario — subscription counted when StudentSessionReport exists ───

test('subscription is counted when session completes with StudentSessionReport', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription();

    createReport($session->id, $session->student_id, AttendanceStatus::ATTENDED);

    $service = app(SessionTransitionService::class);
    $service->handleIndividualSessionCompletion($session);

    $subscription->refresh();
    $session->refresh();

    expect($subscription->sessions_used)->toBe(1);
    expect($subscription->sessions_remaining)->toBe(9);
    expect($session->subscription_counted)->toBeTrue();
    expect($session->status)->toBe(SessionStatus::COMPLETED);
});

// ─── Test 2: absent sessions with StudentSessionReport still count ───

test('subscription is counted for absent sessions with StudentSessionReport', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription();

    createReport($session->id, $session->student_id, AttendanceStatus::ABSENT);

    $service = app(SessionTransitionService::class);
    $service->handleIndividualSessionCompletion($session);

    $subscription->refresh();
    $session->refresh();

    expect($session->status)->toBe(SessionStatus::ABSENT);
    expect($subscription->sessions_used)->toBe(1);
    expect($subscription->sessions_remaining)->toBe(9);
    expect($session->subscription_counted)->toBeTrue();
});

// ─── Test 3: MeetingAttendance fallback (uses AcademicSession — valid DB session_type) ───

test('subscription is counted via MeetingAttendance fallback when no StudentSessionReport exists', function () {
    ['subscription' => $subscription, 'session' => $session] = createAcademicSessionWithSubscription();

    MeetingAttendance::factory()->create([
        'session_id' => $session->id,
        'user_id' => $session->student_id,
        'user_type' => 'student',
        'session_type' => 'academic',
        'attendance_status' => AttendanceStatus::ATTENDED,
    ]);

    $service = app(SessionTransitionService::class);
    $service->handleIndividualSessionCompletion($session);

    $subscription->refresh();
    $session->refresh();

    expect($subscription->sessions_remaining)->toBe(9);
    expect($session->subscription_counted)->toBeTrue();
});

// ─── Test 4: double-counting prevention ───

test('double-counting prevention works', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription();

    createReport($session->id, $session->student_id, AttendanceStatus::ATTENDED);

    $service = app(SessionTransitionService::class);

    // Call twice
    $service->handleIndividualSessionCompletion($session);
    $session->refresh();
    $service->handleIndividualSessionCompletion($session);

    $subscription->refresh();

    expect($subscription->sessions_used)->toBe(1);
    expect($subscription->sessions_remaining)->toBe(9);
});

// ─── Test 5: academic session subscription counting ───

test('academic session subscription counting works', function () {
    ['subscription' => $subscription, 'session' => $session] = createAcademicSessionWithSubscription();

    // Note: StudentSessionReport FK points to quran_sessions table,
    // so we test the no-report path (subscription still counted via fallthrough).
    $service = app(SessionTransitionService::class);
    $service->handleIndividualSessionCompletion($session);

    $subscription->refresh();
    $session->refresh();

    expect($subscription->sessions_remaining)->toBe(9);
    expect($session->subscription_counted)->toBeTrue();
});

// ─── Test 6: MeetingAttendance ABSENT via fallback still counts ───

test('subscription is counted for absent sessions via MeetingAttendance fallback', function () {
    ['subscription' => $subscription, 'session' => $session] = createAcademicSessionWithSubscription();

    MeetingAttendance::factory()->create([
        'session_id' => $session->id,
        'user_id' => $session->student_id,
        'user_type' => 'student',
        'session_type' => 'academic',
        'attendance_status' => AttendanceStatus::ABSENT,
    ]);

    $service = app(SessionTransitionService::class);
    $service->handleIndividualSessionCompletion($session);

    $subscription->refresh();
    $session->refresh();

    expect($session->status)->toBe(SessionStatus::ABSENT);
    expect($subscription->sessions_remaining)->toBe(9);
    expect($session->subscription_counted)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════
// NEW TESTS: Completion path coverage + safety-net command
// ════════════════════════════════════════════════════════════════

// ─── Test 7: autoCompleteIfExpired dispatches SessionCompletedEvent ───

test('autoCompleteIfExpired dispatches SessionCompletedEvent for quran session', function () {
    Event::fake([SessionCompletedEvent::class]);

    $liveKitMock = Mockery::mock(LiveKitService::class);
    $liveKitMock->shouldReceive('endMeeting')->andReturn(true);

    $service = new SessionStatusService($liveKitMock);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::ONGOING,
        'scheduled_at' => now()->subHours(3),
        'started_at' => now()->subHours(3),
        'duration_minutes' => 45,
    ]);

    $result = $service->autoCompleteIfExpired($session);

    expect($result)->toBeTrue();

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::COMPLETED);

    Event::assertDispatched(SessionCompletedEvent::class, function ($event) use ($session) {
        return $event->session->id === $session->id && $event->sessionType === 'quran';
    });
});

test('autoCompleteIfExpired dispatches SessionCompletedEvent for academic session', function () {
    Event::fake([SessionCompletedEvent::class]);

    $liveKitMock = Mockery::mock(LiveKitService::class);
    $liveKitMock->shouldReceive('endMeeting')->andReturn(true);

    $service = new SessionStatusService($liveKitMock);

    $academicTeacher = createAcademicTeacher($this->academy);

    $session = AcademicSession::factory()->create([
        'academy_id' => $this->academy->id,
        'academic_teacher_id' => $academicTeacher->academicTeacherProfile->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::ONGOING,
        'scheduled_at' => now()->subHours(3),
        'started_at' => now()->subHours(3),
        'duration_minutes' => 60,
    ]);

    $result = $service->autoCompleteIfExpired($session);

    expect($result)->toBeTrue();

    Event::assertDispatched(SessionCompletedEvent::class, function ($event) use ($session) {
        return $event->session->id === $session->id && $event->sessionType === 'academic';
    });
});

test('autoCompleteIfExpired does not dispatch event for non-expired sessions', function () {
    Event::fake([SessionCompletedEvent::class]);

    $liveKitMock = Mockery::mock(LiveKitService::class);
    $service = new SessionStatusService($liveKitMock);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'status' => SessionStatus::ONGOING,
        'scheduled_at' => now()->subMinutes(10),
        'started_at' => now()->subMinutes(10),
        'duration_minutes' => 45,
    ]);

    $result = $service->autoCompleteIfExpired($session);

    expect($result)->toBeFalse();
    Event::assertNotDispatched(SessionCompletedEvent::class);
});

// ─── Test 8: Zero remaining sessions doesn't throw ───

test('QuranSubscription useSession allows over-usage when zero sessions remaining', function () {
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'total_sessions' => 4,
        'sessions_used' => 4,
        'sessions_remaining' => 0,
        'status' => SessionSubscriptionStatus::ACTIVE,
    ]);

    // Should NOT throw — previously threw "لا توجد جلسات متبقية في الاشتراك"
    $subscription->useSession();

    $subscription->refresh();
    expect($subscription->sessions_used)->toBe(5);
    expect($subscription->sessions_remaining)->toBe(0); // max(0, 0-1) = 0
});

test('AcademicSubscription useSession allows over-usage when zero sessions remaining', function () {
    $academicTeacher = createAcademicTeacher($this->academy);

    $subscription = AcademicSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'teacher_id' => $academicTeacher->academicTeacherProfile->id,
        'total_sessions' => 4,
        'sessions_used' => 4,
        'sessions_remaining' => 0,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $subscription->useSession();

    $subscription->refresh();
    expect($subscription->sessions_used)->toBe(5);
    expect($subscription->sessions_remaining)->toBe(0);
});

// ─── Test 9: Safety-net reconciliation command ───

test('reconcile command fixes uncounted completed sessions', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription([
        'status' => SessionStatus::COMPLETED,
        'ended_at' => now()->subHours(2),
        'subscription_counted' => false,
    ]);

    $this->artisan('subscriptions:reconcile-missed', ['--minutes' => 1])
        ->assertSuccessful();

    $session->refresh();
    $subscription->refresh();

    expect($session->subscription_counted)->toBeTrue();
    expect($subscription->sessions_used)->toBe(1);
    expect($subscription->sessions_remaining)->toBe(9);
});

test('reconcile command fixes uncounted absent sessions', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription([
        'status' => SessionStatus::ABSENT,
        'ended_at' => now()->subHours(2),
        'subscription_counted' => false,
    ]);

    $this->artisan('subscriptions:reconcile-missed', ['--minutes' => 1])
        ->assertSuccessful();

    $session->refresh();
    $subscription->refresh();

    expect($session->subscription_counted)->toBeTrue();
    expect($subscription->sessions_used)->toBe(1);
});

test('reconcile command dry run does not modify data', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription([
        'status' => SessionStatus::COMPLETED,
        'ended_at' => now()->subHours(2),
        'subscription_counted' => false,
    ]);

    $this->artisan('subscriptions:reconcile-missed', ['--dry-run' => true, '--minutes' => 1])
        ->assertSuccessful();

    $session->refresh();
    $subscription->refresh();

    expect($session->subscription_counted)->toBeFalse();
    expect($subscription->sessions_used)->toBe(0);
});

test('reconcile command skips recently ended sessions', function () {
    ['subscription' => $subscription, 'session' => $session] = createQuranSessionWithSubscription([
        'status' => SessionStatus::COMPLETED,
        'ended_at' => now()->subMinutes(3), // Only 3 min ago, within default 10-min window
        'subscription_counted' => false,
    ]);

    // Default --minutes=10, so session ended 3 min ago should be skipped
    $this->artisan('subscriptions:reconcile-missed')
        ->assertSuccessful();

    $session->refresh();
    expect($session->subscription_counted)->toBeFalse();
});

test('reconcile command also processes academic sessions', function () {
    ['subscription' => $subscription, 'session' => $session] = createAcademicSessionWithSubscription([
        'status' => SessionStatus::COMPLETED,
        'ended_at' => now()->subHours(2),
        'subscription_counted' => false,
    ]);

    $this->artisan('subscriptions:reconcile-missed', ['--minutes' => 1])
        ->assertSuccessful();

    $session->refresh();
    $subscription->refresh();

    expect($session->subscription_counted)->toBeTrue();
    expect($subscription->sessions_used)->toBe(1);
});
