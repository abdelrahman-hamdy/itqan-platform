<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SessionStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    private SessionStatusService $statusService;

    private Academy $academy;

    private User $teacher;

    private User $student;

    private QuranCircle $groupCircle;

    private QuranIndividualCircle $individualCircle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusService = app(SessionStatusService::class);

        // Create test data
        $this->academy = Academy::factory()->create();

        $this->teacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'quran_teacher',
        ]);

        $this->student = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'student',
        ]);

        // Create teacher and student profiles
        QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        StudentProfile::factory()->create([
            'user_id' => $this->student->id,
            'academy_id' => $this->academy->id,
        ]);

        // Create group circle
        $this->groupCircle = QuranCircle::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'preparation_minutes' => 15,
            'ending_buffer_minutes' => 5,
        ]);

        // Create individual circle with subscription
        $package = QuranPackage::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        $subscription = QuranSubscription::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'package_id' => $package->id,
        ]);

        $this->individualCircle = QuranIndividualCircle::factory()->create([
            'academy_id' => $this->academy->id,
            'subscription_id' => $subscription->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'preparation_minutes' => 10,
            'late_join_grace_period_minutes' => 20,
            'ending_buffer_minutes' => 3,
        ]);
    }

    public function test_session_transitions_from_scheduled_to_ready()
    {
        // Create session scheduled 20 minutes from now
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(20),
            'duration_minutes' => 60,
        ]);

        // Session should not transition yet (20 minutes before start, but prep time is 15 minutes)
        $this->assertFalse($this->statusService->shouldTransitionToReady($session));

        // Move time to 10 minutes before session (within preparation time)
        Carbon::setTestNow(now()->addMinutes(10));
        $session->refresh();

        $this->assertTrue($this->statusService->shouldTransitionToReady($session));
        $this->assertTrue($this->statusService->transitionToReady($session));

        $session->refresh();
        $this->assertEquals(SessionStatus::READY, $session->status);
        $this->assertNotNull($session->preparation_completed_at);
    }

    public function test_session_transitions_from_ready_to_ongoing()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::READY,
            'scheduled_at' => now()->addMinutes(5),
        ]);

        $this->assertTrue($this->statusService->transitionToOngoing($session));

        $session->refresh();
        $this->assertEquals(SessionStatus::ONGOING, $session->status);
        $this->assertNotNull($session->started_at);
    }

    public function test_session_auto_completion_based_on_time()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::ONGOING,
            'scheduled_at' => now()->subMinutes(70), // Started 70 minutes ago
            'duration_minutes' => 60,
            'started_at' => now()->subMinutes(65),
        ]);

        // Should auto-complete (60 minutes duration + 5 minutes buffer = 65 minutes total)
        $this->assertTrue($this->statusService->shouldAutoComplete($session));
        $this->assertTrue($this->statusService->transitionToCompleted($session));

        $session->refresh();
        $this->assertEquals(SessionStatus::COMPLETED, $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertGreaterThan(0, $session->actual_duration_minutes);
    }

    public function test_individual_session_transitions_to_absent()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'individual_circle_id' => $this->individualCircle->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'individual',
            'status' => SessionStatus::READY,
            'scheduled_at' => now()->subMinutes(25), // Started 25 minutes ago
            'duration_minutes' => 30,
        ]);

        // Should transition to absent (20 minutes grace period passed)
        $this->assertTrue($this->statusService->shouldTransitionToAbsent($session));
        $this->assertTrue($this->statusService->transitionToAbsent($session));

        $session->refresh();
        $this->assertEquals(SessionStatus::ABSENT, $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertEquals('absent', $session->attendance_status);
    }

    public function test_process_status_transitions_for_multiple_sessions()
    {
        // Create various sessions needing different transitions
        $sessionToReady = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(10), // Within preparation time
            'duration_minutes' => 60,
        ]);

        $sessionToComplete = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::ONGOING,
            'scheduled_at' => now()->subMinutes(70),
            'duration_minutes' => 60,
            'started_at' => now()->subMinutes(65),
        ]);

        $sessionToAbsent = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'individual_circle_id' => $this->individualCircle->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'individual',
            'status' => SessionStatus::READY,
            'scheduled_at' => now()->subMinutes(25),
            'duration_minutes' => 30,
        ]);

        $sessions = collect([$sessionToReady, $sessionToComplete, $sessionToAbsent]);
        $results = $this->statusService->processStatusTransitions($sessions);

        $this->assertEquals(1, $results['transitions_to_ready']);
        $this->assertEquals(1, $results['transitions_to_completed']);
        $this->assertEquals(1, $results['transitions_to_absent']);
        $this->assertEmpty($results['errors']);

        // Verify actual status changes
        $sessionToReady->refresh();
        $sessionToComplete->refresh();
        $sessionToAbsent->refresh();

        $this->assertEquals(SessionStatus::READY, $sessionToReady->status);
        $this->assertEquals(SessionStatus::COMPLETED, $sessionToComplete->status);
        $this->assertEquals(SessionStatus::ABSENT, $sessionToAbsent->status);
    }

    public function test_session_status_command_dry_run()
    {
        // Create session that needs status update
        QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(10),
            'duration_minutes' => 60,
        ]);

        // Run command in dry-run mode
        $this->artisan('sessions:update-statuses', ['--dry-run' => true, '--details' => true])
            ->expectsOutput('🧪 DRY RUN MODE - No changes will be made')
            ->assertExitCode(0);
    }

    public function test_session_status_command_execution()
    {
        // Create session that needs to transition to ready
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(10),
            'duration_minutes' => 60,
        ]);

        // Run the actual command
        $this->artisan('sessions:update-statuses', ['--details' => true])
            ->assertExitCode(0);

        // Verify session status was updated
        $session->refresh();
        $this->assertEquals(SessionStatus::READY, $session->status);
    }

    public function test_invalid_status_transitions_are_prevented()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::COMPLETED, // Already completed
            'scheduled_at' => now()->subMinutes(30),
        ]);

        // Should not be able to transition completed session to ready
        $this->assertFalse($this->statusService->transitionToReady($session));

        // Should not be able to transition completed session to ongoing
        $this->assertFalse($this->statusService->transitionToOngoing($session));

        // Status should remain unchanged
        $session->refresh();
        $this->assertEquals(SessionStatus::COMPLETED, $session->status);
    }

    public function test_academy_specific_processing()
    {
        // Create another academy with sessions
        $otherAcademy = Academy::factory()->create();
        $otherTeacher = User::factory()->create([
            'academy_id' => $otherAcademy->id,
            'user_type' => 'quran_teacher',
        ]);

        QuranTeacherProfile::factory()->create([
            'user_id' => $otherTeacher->id,
            'academy_id' => $otherAcademy->id,
        ]);

        $otherCircle = QuranCircle::factory()->create([
            'academy_id' => $otherAcademy->id,
            'quran_teacher_id' => $otherTeacher->id,
        ]);

        // Create sessions in both academies that need updates
        QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->groupCircle->id,
            'quran_teacher_id' => $this->teacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(10),
        ]);

        QuranSession::factory()->create([
            'academy_id' => $otherAcademy->id,
            'circle_id' => $otherCircle->id,
            'quran_teacher_id' => $otherTeacher->id,
            'session_type' => 'circle',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(10),
        ]);

        // Run command for specific academy only
        $this->artisan('sessions:update-statuses', [
            '--academy-id' => $this->academy->id,
            '--details' => true,
        ])->assertExitCode(0);

        // Only sessions from the specified academy should be processed
        $processedSessions = QuranSession::where('academy_id', $this->academy->id)
            ->where('status', SessionStatus::READY)
            ->count();

        $unprocessedSessions = QuranSession::where('academy_id', $otherAcademy->id)
            ->where('status', SessionStatus::SCHEDULED)
            ->count();

        $this->assertEquals(1, $processedSessions);
        $this->assertEquals(1, $unprocessedSessions);
    }
}
