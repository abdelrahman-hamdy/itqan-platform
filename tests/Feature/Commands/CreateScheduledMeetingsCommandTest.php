<?php

namespace Tests\Feature\Commands;

use App\Enums\SessionStatus;
use App\Models\AcademicSettings;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Models\VideoMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CreateScheduledMeetingsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $teacher;

    private QuranCircle $circle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academy = Academy::factory()->create();

        // Create academic settings with auto meeting creation enabled
        AcademicSettings::factory()->create([
            'academy_id' => $this->academy->id,
            'auto_create_meetings' => true,
            'meeting_creation_hours_before' => 2,
        ]);

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
    public function it_can_run_create_meetings_command_successfully()
    {
        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_creates_meetings_for_scheduled_sessions_within_timeframe()
    {
        // Create a session that should have a meeting created (2 hours from now)
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addHours(1.5), // 1.5 hours from now
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'group',
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // Check if meeting was created
        $this->assertDatabaseHas('video_meetings', [
            'session_id' => $session->id,
            'status' => 'scheduled',
        ]);
    }

    /** @test */
    public function it_does_not_create_meetings_for_sessions_too_far_in_future()
    {
        // Create a session that's too far in the future (4 hours from now)
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addHours(4),
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'group',
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // No meeting should be created
        $this->assertDatabaseMissing('video_meetings', [
            'session_id' => $session->id,
        ]);
    }

    /** @test */
    public function it_does_not_create_duplicate_meetings()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'group',
        ]);

        // Create existing meeting
        VideoMeeting::factory()->create([
            'session_id' => $session->id,
            'status' => 'scheduled',
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // Should still only have one meeting
        $this->assertEquals(1, VideoMeeting::where('session_id', $session->id)->count());
    }

    /** @test */
    public function it_processes_only_specific_academy_when_academy_id_provided()
    {
        $otherAcademy = Academy::factory()->create();

        // Create sessions in both academies
        $session1 = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $session2 = QuranSession::factory()->create([
            'academy_id' => $otherAcademy->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled', [
            '--academy-id' => $this->academy->id,
        ]);

        $this->assertEquals(0, $exitCode);

        // Only session1 should have a meeting created
        $this->assertDatabaseHas('video_meetings', [
            'session_id' => $session1->id,
        ]);

        $this->assertDatabaseMissing('video_meetings', [
            'session_id' => $session2->id,
        ]);
    }

    /** @test */
    public function it_handles_dry_run_mode_without_creating_meetings()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);

        // No meeting should be created in dry run
        $this->assertDatabaseMissing('video_meetings', [
            'session_id' => $session->id,
        ]);
    }

    /** @test */
    public function it_skips_academies_with_auto_create_disabled()
    {
        // Disable auto meeting creation
        $this->academy->academicSettings->update(['auto_create_meetings' => false]);

        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // No meeting should be created
        $this->assertDatabaseMissing('video_meetings', [
            'session_id' => $session->id,
        ]);
    }

    /** @test */
    public function it_handles_different_creation_timeframes_per_academy()
    {
        // Academy with 4-hour advance creation
        $this->academy->academicSettings->update(['meeting_creation_hours_before' => 4]);

        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addHours(3), // 3 hours from now
            'status' => SessionStatus::SCHEDULED,
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // Meeting should be created (within 4-hour window)
        $this->assertDatabaseHas('video_meetings', [
            'session_id' => $session->id,
        ]);
    }

    /** @test */
    public function it_creates_meetings_for_both_group_and_individual_sessions()
    {
        $student = User::factory()->create([
            'academy_id' => $this->academy->id,
            'role' => 'student',
        ]);

        $groupSession = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'group',
        ]);

        $individualSession = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
            'session_type' => 'individual',
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // Both sessions should have meetings created
        $this->assertDatabaseHas('video_meetings', [
            'session_id' => $groupSession->id,
        ]);

        $this->assertDatabaseHas('video_meetings', [
            'session_id' => $individualSession->id,
        ]);
    }

    /** @test */
    public function it_logs_execution_details_and_results()
    {
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        $this->assertEquals(0, $exitCode);

        // Check that command output contains useful information
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function it_handles_meeting_creation_errors_gracefully()
    {
        // Create session with invalid configuration that might cause meeting creation to fail
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'circle_id' => $this->circle->id,
            'scheduled_at' => now()->addHours(1.5),
            'status' => SessionStatus::SCHEDULED,
            'duration_minutes' => null, // Invalid duration
        ]);

        $exitCode = Artisan::call('meetings:create-scheduled');

        // Command should complete successfully even if individual meetings fail
        $this->assertEquals(0, $exitCode);
    }
}
