<?php

namespace Tests\Feature\Commands;

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SessionStatusUpdateCommandTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $teacher;

    private QuranCircle $circle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'role' => 'teacher',
        ]);
        $this->circle = QuranCircle::factory()->create([
            'academy_id' => $this->academy->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    /** @test */
    public function it_can_run_status_update_command_successfully()
    {
        $exitCode = Artisan::call('sessions:update-statuses', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Starting enhanced session status update process', Artisan::output());
    }

    /** @test */
    public function it_transitions_scheduled_sessions_to_ready_when_preparation_time_arrives()
    {
        // Create a session that should transition to READY (15 minutes before start time)
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addMinutes(10), // 10 minutes from now
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'group',
        ]);

        // Mock the time to be 25 minutes from now (15 minutes before session)
        Carbon::setTestNow(now()->addMinutes(25));

        $exitCode = Artisan::call('sessions:update-statuses');

        $this->assertEquals(0, $exitCode);

        $session->refresh();
        $this->assertEquals(SessionStatus::READY, $session->status);
        $this->assertNotNull($session->preparation_completed_at);
    }

    /** @test */
    public function it_transitions_individual_sessions_to_absent_when_grace_period_expires()
    {
        $student = User::factory()->create([
            'academy_id' => $this->academy->id,
            'role' => 'student',
        ]);

        // Create an individual session in READY status
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->subMinutes(20), // 20 minutes ago
            'status' => SessionStatus::READY,
            'session_type' => 'individual',
        ]);

        // Mock the time to be past grace period (15 minutes after session start)
        Carbon::setTestNow(now());

        $exitCode = Artisan::call('sessions:update-statuses');

        $this->assertEquals(0, $exitCode);

        $session->refresh();
        $this->assertEquals(SessionStatus::ABSENT, $session->status);
    }

    /** @test */
    public function it_auto_completes_ongoing_sessions_after_duration_plus_buffer()
    {
        // Create an ongoing session that should auto-complete
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->subMinutes(65), // 65 minutes ago
            'duration_minutes' => 60,
            'status' => SessionStatus::ONGOING,
            'started_at' => now()->subMinutes(65),
            'session_type' => 'group',
        ]);

        // Mock the time to be past session end + buffer (5 minutes)
        Carbon::setTestNow(now());

        $exitCode = Artisan::call('sessions:update-statuses');

        $this->assertEquals(0, $exitCode);

        $session->refresh();
        $this->assertEquals(SessionStatus::COMPLETED, $session->status);
        $this->assertNotNull($session->ended_at);
    }

    /** @test */
    public function it_processes_only_specific_academy_when_academy_id_provided()
    {
        $otherAcademy = Academy::factory()->create();

        // Create sessions in both academies
        $session1 = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addMinutes(10),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $session2 = QuranSession::factory()->create([
            'academy_id' => $otherAcademy->id,
            'scheduled_at' => now()->addMinutes(10),
            'status' => SessionStatus::SCHEDULED,
        ]);

        Carbon::setTestNow(now()->addMinutes(25));

        $exitCode = Artisan::call('sessions:update-statuses', [
            '--academy-id' => $this->academy->id,
        ]);

        $this->assertEquals(0, $exitCode);

        $session1->refresh();
        $session2->refresh();

        // Only session1 should be updated
        $this->assertEquals(SessionStatus::READY, $session1->status);
        $this->assertEquals(SessionStatus::SCHEDULED, $session2->status);
    }

    /** @test */
    public function it_handles_dry_run_mode_without_making_changes()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addMinutes(10),
            'status' => SessionStatus::SCHEDULED,
        ]);

        Carbon::setTestNow(now()->addMinutes(25));

        $exitCode = Artisan::call('sessions:update-statuses', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', Artisan::output());

        $session->refresh();
        // Status should not change in dry run
        $this->assertEquals(SessionStatus::SCHEDULED, $session->status);
    }

    /** @test */
    public function it_logs_execution_details_properly()
    {
        $this->expectsEvents();

        $exitCode = Artisan::call('sessions:update-statuses', ['--details' => true]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Starting enhanced session status update process', $output);
        $this->assertStringContainsString('Current time:', $output);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Create a session with invalid data to trigger an error
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => null, // Invalid - no circle
            'scheduled_at' => now()->addMinutes(10),
            'status' => SessionStatus::SCHEDULED,
        ]);

        Carbon::setTestNow(now()->addMinutes(25));

        $exitCode = Artisan::call('sessions:update-statuses');

        // Should still return success even with individual errors
        $this->assertEquals(0, $exitCode);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }
}
