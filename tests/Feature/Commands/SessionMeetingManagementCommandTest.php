<?php

namespace Tests\Feature\Commands;

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Models\VideoMeeting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SessionMeetingManagementCommandTest extends TestCase
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
    public function it_can_run_meeting_management_command_successfully()
    {
        $exitCode = Artisan::call('sessions:manage-meetings', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Starting session meeting management', Artisan::output());
    }

    /** @test */
    public function it_creates_meetings_for_ready_sessions()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addMinutes(30),
            'status' => SessionStatus::READY,
            'session_type' => 'group',
        ]);

        $exitCode = Artisan::call('sessions:manage-meetings');

        $this->assertEquals(0, $exitCode);

        // Check if meeting was created
        $this->assertDatabaseHas('video_meetings', [
            'session_id' => $session->id,
            'status' => 'scheduled',
        ]);
    }

    /** @test */
    public function it_updates_session_statuses_during_processing()
    {
        // Create a session that should transition to READY
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addMinutes(10),
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'group',
        ]);

        // Mock time to trigger READY transition
        Carbon::setTestNow(now()->addMinutes(25));

        $exitCode = Artisan::call('sessions:manage-meetings');

        $this->assertEquals(0, $exitCode);

        $session->refresh();
        $this->assertEquals(SessionStatus::READY, $session->status);
    }

    /** @test */
    public function it_cleans_up_expired_meetings()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->subHours(2),
            'status' => SessionStatus::COMPLETED,
        ]);

        $meeting = VideoMeeting::factory()->create([
            'session_id' => $session->id,
            'status' => 'active',
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        $exitCode = Artisan::call('sessions:manage-meetings');

        $this->assertEquals(0, $exitCode);

        $meeting->refresh();
        $this->assertEquals('ended', $meeting->status);
    }

    /** @test */
    public function it_runs_in_maintenance_mode_during_off_hours()
    {
        // Mock time to be during off hours (2 AM)
        Carbon::setTestNow(Carbon::today()->addHours(2));

        $exitCode = Artisan::call('sessions:manage-meetings');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Off hours detected', Artisan::output());
    }

    /** @test */
    public function it_can_be_forced_to_run_during_off_hours()
    {
        // Mock time to be during off hours (2 AM)
        Carbon::setTestNow(Carbon::today()->addHours(2));

        $exitCode = Artisan::call('sessions:manage-meetings', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringNotContainsString('Off hours detected', Artisan::output());
    }

    /** @test */
    public function it_handles_dry_run_mode()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addMinutes(30),
            'status' => SessionStatus::READY,
        ]);

        $exitCode = Artisan::call('sessions:manage-meetings', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', Artisan::output());

        // No meeting should be created
        $this->assertDatabaseMissing('video_meetings', [
            'session_id' => $session->id,
        ]);
    }

    /** @test */
    public function it_logs_detailed_execution_information()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addMinutes(30),
            'status' => SessionStatus::READY,
        ]);

        $exitCode = Artisan::call('sessions:manage-meetings');

        $this->assertEquals(0, $exitCode);

        // Check log files exist
        $this->assertFileExists(storage_path('logs/cron/sessions:manage-meetings.log'));
    }

    /** @test */
    public function it_processes_multiple_sessions_efficiently()
    {
        // Create multiple sessions in different states
        $sessions = collect(range(1, 5))->map(function ($i) {
            return QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $this->circle->id,
                'teacher_id' => $this->teacher->id,
                'scheduled_at' => now()->addMinutes(10 + $i),
                'status' => SessionStatus::SCHEDULED,
                'session_type' => 'group',
            ]);
        });

        Carbon::setTestNow(now()->addMinutes(25));

        $exitCode = Artisan::call('sessions:manage-meetings');

        $this->assertEquals(0, $exitCode);

        // All sessions should be processed
        $sessions->each(function ($session) {
            $session->refresh();
            $this->assertEquals(SessionStatus::READY, $session->status);
        });
    }

    /** @test */
    public function it_handles_service_errors_gracefully()
    {
        // Create session with invalid data
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => null, // Invalid
            'scheduled_at' => now()->addMinutes(30),
            'status' => SessionStatus::READY,
        ]);

        $exitCode = Artisan::call('sessions:manage-meetings');

        // Should not fail completely due to one error
        $this->assertEquals(0, $exitCode);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }
}
