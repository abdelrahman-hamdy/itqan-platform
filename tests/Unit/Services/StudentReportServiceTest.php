<?php

namespace Tests\Unit\Services;

use App\Models\Academy;
use App\Models\MeetingAttendance;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\StudentReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected $academy;

    protected $teacher;

    protected $student;

    protected $session;

    protected $circle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StudentReportService::class);

        // Create test data
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->create([
            'user_type' => 'quran_teacher',
            'academy_id' => $this->academy->id,
        ]);
        $this->student = User::factory()->create([
            'user_type' => 'student',
            'academy_id' => $this->academy->id,
        ]);
        $this->circle = QuranCircle::factory()->create([
            'academy_id' => $this->academy->id,
            'teacher_id' => $this->teacher->id,
            'max_late_minutes' => 10,
        ]);
        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::parse('2024-01-01 10:00:00'),
            'duration_minutes' => 60,
        ]);
    }

    public function test_generate_student_report_with_meeting_attendance()
    {
        // Create meeting attendance
        $meetingAttendance = MeetingAttendance::create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'user_type' => 'student',
            'session_type' => 'individual',
            'first_join_time' => Carbon::parse('2024-01-01 10:05:00'), // 5 minutes late
            'last_leave_time' => Carbon::parse('2024-01-01 10:55:00'),
            'total_duration_minutes' => 50,
            'join_leave_cycles' => [
                ['joined_at' => '2024-01-01T10:05:00Z', 'left_at' => '2024-01-01T10:55:00Z', 'duration_minutes' => 50],
            ],
        ]);

        $report = $this->service->generateStudentReport($this->session, $this->student);

        $this->assertInstanceOf(StudentSessionReport::class, $report);
        $this->assertEquals($this->session->id, $report->session_id);
        $this->assertEquals($this->student->id, $report->student_id);
        $this->assertEquals($this->teacher->id, $report->teacher_id);
        $this->assertEquals('present', $report->attendance_status); // 50/60 = 83% attendance
        $this->assertEquals(83.33, round($report->attendance_percentage, 2));
        $this->assertEquals(5, $report->late_minutes);
        $this->assertFalse($report->is_late); // 5 minutes is within 10 minute limit
        $this->assertEquals(50, $report->actual_attendance_minutes);
        $this->assertTrue($report->is_auto_calculated);
    }

    public function test_generate_student_report_without_meeting_attendance()
    {
        $report = $this->service->generateStudentReport($this->session, $this->student);

        $this->assertInstanceOf(StudentSessionReport::class, $report);
        $this->assertEquals('absent', $report->attendance_status);
        $this->assertEquals(0, $report->attendance_percentage);
        $this->assertEquals(0, $report->actual_attendance_minutes);
        $this->assertFalse($report->is_late);
        $this->assertEquals(0, $report->late_minutes);
        $this->assertTrue($report->is_auto_calculated);
    }

    public function test_calculate_attendance_status_scenarios()
    {
        $scenarios = [
            // [actualMinutes, sessionDuration, lateMinutes, maxLateMinutes, expectedStatus]
            [0, 60, 0, 10, 'absent'],           // No attendance
            [15, 60, 0, 10, 'absent'],          // Less than 30% attendance
            [25, 60, 0, 10, 'partial'],         // 42% attendance
            [45, 60, 0, 10, 'present'],         // 75% attendance
            [45, 60, 5, 10, 'late'],            // 75% attendance but late
            [45, 60, 15, 10, 'absent'],         // Too late (over max late minutes)
        ];

        foreach ($scenarios as [$actualMinutes, $sessionDuration, $lateMinutes, $maxLateMinutes, $expectedStatus]) {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'session_type' => 'individual',
                'scheduled_at' => Carbon::parse('2024-01-01 10:00:00'),
                'duration_minutes' => $sessionDuration,
            ]);

            $startTime = Carbon::parse('2024-01-01 10:00:00')->addMinutes($lateMinutes);
            $endTime = $startTime->copy()->addMinutes($actualMinutes);

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $actualMinutes > 0 ? $startTime : null,
                'last_leave_time' => $actualMinutes > 0 ? $endTime : null,
                'total_duration_minutes' => $actualMinutes,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            $this->assertEquals($expectedStatus, $report->attendance_status,
                "Failed for scenario: {$actualMinutes}min, {$sessionDuration}min session, {$lateMinutes}min late");
        }
    }

    public function test_connection_quality_calculation()
    {
        $scenarios = [
            // [cycles, expectedScore]
            [[], 100],                           // No disconnections = perfect
            [['join1', 'leave1'], 100],          // Single connection = perfect
            [['join1', 'leave1', 'join2', 'leave2'], 90], // One disconnection = -10
            [['join1', 'leave1', 'join2', 'leave2', 'join3', 'leave3'], 80], // Two disconnections = -20
        ];

        foreach ($scenarios as [$cycles, $expectedScore]) {
            MeetingAttendance::create([
                'session_id' => $this->session->id,
                'user_id' => $this->student->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => Carbon::parse('2024-01-01 10:00:00'),
                'last_leave_time' => Carbon::parse('2024-01-01 10:50:00'),
                'total_duration_minutes' => 50,
                'join_leave_cycles' => $cycles,
            ]);

            $report = $this->service->generateStudentReport($this->session, $this->student);

            $this->assertEquals($expectedScore, $report->connection_quality_score,
                'Failed for cycles: '.json_encode($cycles));

            // Cleanup for next iteration
            MeetingAttendance::where('session_id', $this->session->id)->delete();
            StudentSessionReport::where('session_id', $this->session->id)->delete();
        }
    }

    public function test_update_teacher_evaluation()
    {
        $report = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $updatedReport = $this->service->updateTeacherEvaluation(
            $report,
            8.5,
            7.0,
            'تحسن ملحوظ في الحفظ'
        );

        $this->assertEquals(8.5, $updatedReport->new_memorization_degree);
        $this->assertEquals(7.0, $updatedReport->reservation_degree);
        $this->assertEquals('تحسن ملحوظ في الحفظ', $updatedReport->notes);
        $this->assertTrue($updatedReport->manually_evaluated);
        $this->assertNotNull($updatedReport->evaluated_at);
    }

    public function test_generate_session_reports_for_group()
    {
        // Create group session
        $groupSession = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'circle_id' => $this->circle->id,
            'session_type' => 'group',
            'scheduled_at' => Carbon::parse('2024-01-01 10:00:00'),
            'duration_minutes' => 60,
        ]);

        // Create multiple students and add them to circle
        $students = User::factory()->count(3)->create([
            'user_type' => 'student',
            'academy_id' => $this->academy->id,
        ]);

        // Mock circle students relationship
        $this->circle->students()->attach($students->pluck('id'));

        $reports = $this->service->generateSessionReports($groupSession);

        $this->assertCount(3, $reports);
        foreach ($reports as $report) {
            $this->assertEquals($groupSession->id, $report->session_id);
            $this->assertEquals($this->teacher->id, $report->teacher_id);
            $this->assertTrue($students->pluck('id')->contains($report->student_id));
        }
    }

    public function test_get_session_stats()
    {
        // Create multiple reports with different statuses
        $reports = collect([
            StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => 'present',
                'attendance_percentage' => 95,
                'new_memorization_degree' => 8.0,
                'reservation_degree' => 7.5,
                'connection_quality_score' => 85,
            ]),
            StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => 'late',
                'attendance_percentage' => 80,
                'new_memorization_degree' => 7.0,
                'reservation_degree' => 6.5,
                'connection_quality_score' => 75,
            ]),
            StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => 'absent',
                'attendance_percentage' => 0,
                'connection_quality_score' => 0,
            ]),
        ]);

        $stats = $this->service->getSessionStats($this->session);

        $this->assertEquals(3, $stats['total_students']);
        $this->assertEquals(1, $stats['present_count']);
        $this->assertEquals(1, $stats['late_count']);
        $this->assertEquals(1, $stats['absent_count']);
        $this->assertEquals(0, $stats['partial_count']);
        $this->assertEquals(58.33, round($stats['avg_attendance_percentage'], 2)); // (95+80+0)/3
        $this->assertEquals(7.5, $stats['avg_memorization_degree']); // (8+7)/2
        $this->assertEquals(7.0, $stats['avg_reservation_degree']); // (7.5+6.5)/2
        $this->assertEquals(53.33, round($stats['avg_connection_quality'], 2)); // (85+75+0)/3
    }

    public function test_get_student_stats()
    {
        $sessionIds = collect();

        // Create multiple sessions and reports for student
        for ($i = 0; $i < 5; $i++) {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'session_type' => 'individual',
            ]);
            $sessionIds->push($session->id);

            StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => $i < 3 ? 'present' : 'absent',
                'new_memorization_degree' => 8.0 - $i * 0.5, // Declining trend
                'reservation_degree' => 7.0,
                'attendance_percentage' => $i < 3 ? 90 : 0,
            ]);
        }

        $stats = $this->service->getStudentStats($this->student, $sessionIds);

        $this->assertEquals(5, $stats['total_sessions']);
        $this->assertEquals(3, $stats['attended_sessions']);
        $this->assertEquals(2, $stats['missed_sessions']);
        $this->assertEquals(60, $stats['attendance_rate']); // 3/5 * 100
        $this->assertEquals(7.0, $stats['avg_memorization_degree']); // (8+7.5+7+6.5+6)/5
        $this->assertEquals(7.0, $stats['avg_reservation_degree']);
        $this->assertEquals(54, $stats['avg_attendance_percentage']); // (90*3 + 0*2)/5
        $this->assertEquals('declining', $stats['improvement_trend']);
    }

    public function test_improvement_trend_calculation()
    {
        $scenarios = [
            // [recentAvg, olderAvg, expectedTrend]
            [8.0, 7.0, 'improving'],    // Recent > older by 1.0
            [7.0, 8.0, 'declining'],   // Recent < older by 1.0
            [7.5, 7.3, 'stable'],      // Difference less than 0.5
            [7.0, 7.0, 'stable'],      // No difference
        ];

        foreach ($scenarios as [$recentAvg, $olderAvg, $expectedTrend]) {
            // Create reports with specific patterns
            $sessionIds = collect();

            // Create older reports
            for ($i = 0; $i < 5; $i++) {
                $session = QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'student_id' => $this->student->id,
                ]);
                $sessionIds->push($session->id);

                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => $olderAvg,
                    'created_at' => now()->subDays(10 + $i),
                ]);
            }

            // Create recent reports
            for ($i = 0; $i < 5; $i++) {
                $session = QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'student_id' => $this->student->id,
                ]);
                $sessionIds->push($session->id);

                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => $recentAvg,
                    'created_at' => now()->subDays($i),
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessionIds);

            $this->assertEquals($expectedTrend, $stats['improvement_trend'],
                "Failed for recent: {$recentAvg}, older: {$olderAvg}");

            // Cleanup for next iteration
            StudentSessionReport::where('student_id', $this->student->id)->delete();
            QuranSession::whereIn('id', $sessionIds)->delete();
        }
    }
}
