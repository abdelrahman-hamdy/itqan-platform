<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\IndividualCircle;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\UnifiedAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private Academy $academy;

    private IndividualCircle $circle;

    private QuranSession $session;

    private UnifiedAttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('quran_teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        // Create academy and circle
        $this->academy = Academy::factory()->create();

        $this->circle = IndividualCircle::factory()->create([
            'academy_id' => $this->academy->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'late_join_grace_period_minutes' => 15,
        ]);

        // Create test session
        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'session_type' => 'individual',
            'individual_circle_id' => $this->circle->id,
            'quran_teacher_id' => $this->teacher->id,
            'scheduled_at' => now()->subMinutes(30), // Session started 30 minutes ago
            'duration_minutes' => 60,
            'status' => SessionStatus::ONGOING,
        ]);

        $this->attendanceService = app(UnifiedAttendanceService::class);
    }

    /** @test */
    public function it_calculates_attendance_minutes_for_active_users()
    {
        // Create meeting attendance record - student joined 20 minutes ago
        $joinTime = now()->subMinutes(20);

        $meetingAttendance = MeetingAttendance::create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'user_type' => 'student',
            'session_type' => 'individual',
            'first_join_time' => $joinTime,
            'join_leave_cycles' => [
                ['joined_at' => $joinTime->toISOString()],
            ],
            'join_count' => 1,
            'leave_count' => 0,
            'total_duration_minutes' => 0, // This should be updated
        ]);

        // Create student session report
        $report = StudentSessionReport::create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'actual_attendance_minutes' => 0,
        ]);

        // Get current attendance status (this should trigger sync)
        $status = $this->attendanceService->getCurrentAttendanceStatus($this->session, $this->student);

        // Refresh the report to see updated values
        $report->refresh();

        // Assertions
        $this->assertTrue($status['is_currently_in_meeting']);
        $this->assertEquals('present', $status['attendance_status']);
        $this->assertGreaterThan(15, $status['duration_minutes']); // Should be around 20 minutes
        $this->assertGreaterThan(15, $report->actual_attendance_minutes); // Should be around 20 minutes

        // Should show real-time calculation
        $this->assertEqualsWithDelta(20, $status['duration_minutes'], 2); // Allow 2-minute delta
    }

    /** @test */
    public function it_handles_grace_time_violations_persistently()
    {
        // Student joins 20 minutes after session start (violates 15-minute grace period)
        $lateJoinTime = $this->session->scheduled_at->copy()->addMinutes(20);
        Carbon::setTestNow($lateJoinTime);

        $meetingAttendance = MeetingAttendance::create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'user_type' => 'student',
            'session_type' => 'individual',
            'first_join_time' => $lateJoinTime,
            'join_leave_cycles' => [
                ['joined_at' => $lateJoinTime->toISOString()],
            ],
            'join_count' => 1,
            'leave_count' => 0,
        ]);

        $report = StudentSessionReport::create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'actual_attendance_minutes' => 0,
        ]);

        // Simulate student staying for full session duration (40 minutes remaining)
        Carbon::setTestNow($lateJoinTime->copy()->addMinutes(40));

        // Get status after staying for full duration
        $status = $this->attendanceService->getCurrentAttendanceStatus($this->session, $this->student);
        $report->refresh();

        // Should be marked as absent due to late join, regardless of duration
        $this->assertEquals('absent', $report->attendance_status);
        $this->assertTrue($report->is_late);
        $this->assertEquals(20, $report->late_minutes);
    }

    /** @test */
    public function it_handles_leave_and_rejoin_scenarios()
    {
        // Student joins on time
        $joinTime = $this->session->scheduled_at->copy()->addMinutes(5); // 5 minutes after start
        Carbon::setTestNow($joinTime);

        $meetingAttendance = MeetingAttendance::create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'user_type' => 'student',
            'session_type' => 'individual',
            'first_join_time' => $joinTime,
            'join_leave_cycles' => [
                [
                    'joined_at' => $joinTime->toISOString(),
                    'left_at' => $joinTime->copy()->addMinutes(20)->toISOString(),
                    'duration_minutes' => 20,
                ],
                [
                    'joined_at' => $joinTime->copy()->addMinutes(30)->toISOString(),
                ],
            ],
            'join_count' => 2,
            'leave_count' => 1,
            'total_duration_minutes' => 20, // Only completed cycles
        ]);

        $report = StudentSessionReport::create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        // Check status 10 minutes after rejoining
        Carbon::setTestNow($joinTime->copy()->addMinutes(40));

        $status = $this->attendanceService->getCurrentAttendanceStatus($this->session, $this->student);
        $report->refresh();

        // Should include both completed and current active time
        $this->assertTrue($status['is_currently_in_meeting']);
        $this->assertGreaterThan(25, $status['duration_minutes']); // 20 + current ~10 minutes
        $this->assertFalse($report->is_late); // First join was within grace period
    }

    /** @test */
    public function it_applies_80_percent_attendance_rule()
    {
        // 60-minute session, need 48 minutes for 80% attendance
        $joinTime = $this->session->scheduled_at->copy()->addMinutes(5);

        $meetingAttendance = MeetingAttendance::create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'user_type' => 'student',
            'session_type' => 'individual',
            'first_join_time' => $joinTime,
            'join_leave_cycles' => [
                [
                    'joined_at' => $joinTime->toISOString(),
                    'left_at' => $joinTime->copy()->addMinutes(30)->toISOString(),
                    'duration_minutes' => 30,
                ],
            ],
            'join_count' => 1,
            'leave_count' => 1,
            'total_duration_minutes' => 30, // Only 50% attendance
        ]);

        $report = StudentSessionReport::create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $status = $this->attendanceService->getCurrentAttendanceStatus($this->session, $this->student);
        $report->refresh();

        // Should be marked as partial attendance (30-79%)
        $this->assertEquals('partial', $report->attendance_status);
        $this->assertEquals(50.0, $report->attendance_percentage);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}
