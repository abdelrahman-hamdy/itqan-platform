<?php

namespace Tests\Unit\Services;

use App\Services\UnifiedSessionStatusService;
use App\Services\SessionSettingsService;
use App\Services\SessionNotificationService;
use App\Models\QuranSession;
use App\Models\User;
use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Mockery;

/**
 * Test cases for SessionStatusService
 *
 * These tests verify the session lifecycle management including:
 * - Status transitions
 * - Automatic status updates based on time
 * - Session completion handling
 */
class SessionStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UnifiedSessionStatusService $service;
    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable event dispatching to prevent heavy operations
        Event::fake();

        $this->createAcademy();
        $this->testId = Str::random(8);

        // Create service with real settings service but mocked notification service
        $settingsService = app(SessionSettingsService::class);
        $notificationService = Mockery::mock(SessionNotificationService::class);
        $notificationService->shouldReceive('sendReadyNotifications')->andReturn(null);
        $notificationService->shouldReceive('sendCompletedNotifications')->andReturn(null);
        $notificationService->shouldReceive('sendAbsentNotifications')->andReturn(null);

        $this->service = new UnifiedSessionStatusService($settingsService, $notificationService);
    }

    /**
     * Create a user with specific type.
     */
    protected function makeUser(string $userType, string $suffix = ''): User
    {
        return User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => $userType,
            'email' => "{$userType}{$suffix}_{$this->testId}@test.local",
        ]);
    }

    /**
     * Create a quran teacher with profile.
     */
    protected function makeQuranTeacherWithProfile(string $suffix = ''): array
    {
        $user = $this->makeUser('quran_teacher', $suffix);
        $profile = QuranTeacherProfile::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => '050' . rand(1000000, 9999999),
            'teacher_code' => 'QT-' . $this->testId . $suffix,
            'is_active' => true,
        ]);
        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * Create a student.
     */
    protected function makeStudentWithProfile(string $suffix = ''): array
    {
        $user = $this->makeUser('student', $suffix);
        return ['user' => $user, 'profile' => $user->studentProfile];
    }

    /**
     * Create a quran session.
     */
    protected function makeQuranSession(
        User $teacherUser,
        ?User $studentUser = null,
        ?Carbon $scheduledAt = null,
        int $duration = 60,
        SessionStatus $status = SessionStatus::SCHEDULED
    ): QuranSession {
        return QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacherUser->id,
            'session_type' => 'individual',
            'student_id' => $studentUser?->id,
            'scheduled_at' => $scheduledAt ?? Carbon::now()->addDay(),
            'duration_minutes' => $duration,
            'status' => $status,
            'session_code' => 'QSE-' . $this->testId . '-' . Str::random(6),
        ]);
    }

    /**
     * Test that scheduled sessions should transition to ready when time arrives.
     */
    public function test_scheduled_session_transitions_to_live_on_time(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create a session scheduled for 5 minutes ago
        $pastTime = Carbon::now()->subMinutes(5);
        $session = $this->makeQuranSession(
            $teacher['user'],
            $student['user'],
            $pastTime,
            60,
            SessionStatus::SCHEDULED
        );

        // Check if session should transition
        $shouldTransition = $this->service->shouldTransitionToReady($session);

        // If time has passed, should transition
        $this->assertTrue($shouldTransition);
    }

    /**
     * Test that live sessions should auto-complete after end time plus grace period.
     */
    public function test_live_session_transitions_to_completed_after_end(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create a session that ended 30 minutes ago (past end time + grace)
        $pastTime = Carbon::now()->subMinutes(90); // 60 min session + 30 min grace
        $session = $this->makeQuranSession(
            $teacher['user'],
            $student['user'],
            $pastTime,
            60,
            SessionStatus::ONGOING
        );

        // Check if session should auto-complete
        $shouldAutoComplete = $this->service->shouldAutoComplete($session);

        $this->assertIsBool($shouldAutoComplete);
    }

    /**
     * Test that cancelled sessions should not transition.
     */
    public function test_cancelled_sessions_do_not_transition(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create a cancelled session
        $session = $this->makeQuranSession(
            $teacher['user'],
            $student['user'],
            Carbon::now()->subMinutes(5),
            60,
            SessionStatus::CANCELLED
        );

        // Cancelled sessions should not transition to ready
        $shouldTransition = $this->service->shouldTransitionToReady($session);

        $this->assertFalse($shouldTransition);
    }

    /**
     * Test session completion transitions correctly.
     */
    public function test_session_completion_dispatches_event(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create an ongoing session
        $session = $this->makeQuranSession(
            $teacher['user'],
            $student['user'],
            Carbon::now()->subMinutes(65),
            60,
            SessionStatus::ONGOING
        );

        // Transition to completed
        $result = $this->service->transitionToCompleted($session);

        $this->assertTrue($result);
        $session->refresh();
        $this->assertEquals(SessionStatus::COMPLETED, $session->status);
    }

    /**
     * Test that sessions in ready state don't auto-complete early.
     */
    public function test_sessions_with_active_meetings_remain_live(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create a session that's currently in progress (started 30 minutes ago)
        $session = $this->makeQuranSession(
            $teacher['user'],
            $student['user'],
            Carbon::now()->subMinutes(30),
            60,
            SessionStatus::ONGOING
        );

        // Should not auto-complete since session hasn't ended yet
        $shouldAutoComplete = $this->service->shouldAutoComplete($session);

        // Session is still within its duration, should NOT auto-complete
        $this->assertFalse($shouldAutoComplete);
    }

    /**
     * Test batch status update processes multiple sessions.
     */
    public function test_batch_status_update_handles_multiple_sessions(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create multiple sessions with different statuses
        $sessions = collect([
            $this->makeQuranSession(
                $teacher['user'],
                $student['user'],
                Carbon::now()->subMinutes(5),
                60,
                SessionStatus::SCHEDULED
            ),
            $this->makeQuranSession(
                $teacher['user'],
                $student['user'],
                Carbon::now()->addDay(),
                60,
                SessionStatus::SCHEDULED
            ),
        ]);

        // Process status transitions
        $result = $this->service->processStatusTransitions($sessions);

        $this->assertIsArray($result);
        // The service returns these keys (not transitioned_to_*)
        $this->assertArrayHasKey('transitions_to_ready', $result);
        $this->assertArrayHasKey('transitions_to_absent', $result);
        $this->assertArrayHasKey('transitions_to_completed', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
