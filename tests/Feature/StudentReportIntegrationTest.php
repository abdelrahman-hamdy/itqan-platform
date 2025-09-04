<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\MeetingAttendance;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\QuranAttendanceService;
use App\Services\StudentReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StudentReportIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $academy;

    protected $teacher;

    protected $student;

    protected $session;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::parse('2024-01-01 10:00:00'),
            'duration_minutes' => 60,
        ]);
    }

    public function test_complete_attendance_tracking_workflow()
    {
        $attendanceService = app(QuranAttendanceService::class);

        // Simulate student joining meeting
        $attendanceService->trackMeetingEvent(
            $this->session->id,
            $this->student->id,
            'join',
            ['timestamp' => '2024-01-01T10:05:00Z']
        );

        // Verify meeting attendance was created
        $meetingAttendance = MeetingAttendance::where('session_id', $this->session->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($meetingAttendance);
        $this->assertEquals($this->student->id, $meetingAttendance->user_id);
        $this->assertEquals(1, $meetingAttendance->join_count);
        $this->assertNotNull($meetingAttendance->first_join_time);

        // Verify student report was generated/updated
        $report = StudentSessionReport::where('session_id', $this->session->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($report);
        $this->assertTrue($report->is_auto_calculated);

        // Simulate student leaving meeting
        $attendanceService->trackMeetingEvent(
            $this->session->id,
            $this->student->id,
            'leave',
            ['timestamp' => '2024-01-01T10:55:00Z']
        );

        // Verify updated meeting attendance
        $meetingAttendance->refresh();
        $this->assertEquals(1, $meetingAttendance->leave_count);
        $this->assertNotNull($meetingAttendance->last_leave_time);
        $this->assertEquals(50, $meetingAttendance->total_duration_minutes);

        // Verify updated student report
        $report->refresh();
        $this->assertEquals(50, $report->actual_attendance_minutes);
        $this->assertEquals('present', $report->attendance_status); // 50/60 = 83%
        $this->assertTrue($report->is_late); // 5 minutes late
        $this->assertEquals(5, $report->late_minutes);
    }

    public function test_teacher_manual_evaluation_workflow()
    {
        // Generate initial auto-calculated report
        $reportService = app(StudentReportService::class);
        $report = $reportService->generateStudentReport($this->session, $this->student);

        $this->assertTrue($report->is_auto_calculated);
        $this->assertFalse($report->manually_evaluated);
        $this->assertNull($report->new_memorization_degree);

        // Teacher updates evaluation
        $updatedReport = $reportService->updateTeacherEvaluation(
            $report,
            8.5,
            7.0,
            'أداء ممتاز في الحفظ والمراجعة'
        );

        $this->assertEquals(8.5, $updatedReport->new_memorization_degree);
        $this->assertEquals(7.0, $updatedReport->reservation_degree);
        $this->assertEquals('أداء ممتاز في الحفظ والمراجعة', $updatedReport->notes);
        $this->assertTrue($updatedReport->manually_evaluated);
        $this->assertNotNull($updatedReport->evaluated_at);
    }

    public function test_api_endpoints_integration()
    {
        $this->actingAs($this->teacher);

        // Test getting basic student info
        $response = $this->getJson("/teacher/test-academy/students/{$this->student->id}/basic-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'student' => [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                ],
            ]);

        // Create a report first
        $report = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        // Test getting report
        $response = $this->getJson("/teacher/test-academy/student-reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report' => [
                    'id' => $report->id,
                    'student' => [
                        'id' => $this->student->id,
                        'name' => $this->student->name,
                    ],
                ],
            ]);

        // Test updating evaluation
        $response = $this->postJson('/teacher/test-academy/student-reports/update', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'report_id' => $report->id,
            'new_memorization_degree' => 9.0,
            'reservation_degree' => 8.5,
            'notes' => 'تحسن كبير',
            'attendance_status' => 'present',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حفظ التقييم بنجاح',
            ]);

        // Verify the update
        $report->refresh();
        $this->assertEquals(9.0, $report->new_memorization_degree);
        $this->assertEquals(8.5, $report->reservation_degree);
        $this->assertEquals('تحسن كبير', $report->notes);
        $this->assertEquals('present', $report->attendance_status);
        $this->assertTrue($report->manually_evaluated);
    }

    public function test_group_session_multiple_students()
    {
        // Create group session with circle
        $circle = QuranCircle::factory()->create([
            'academy_id' => $this->academy->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $groupSession = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'circle_id' => $circle->id,
            'session_type' => 'group',
            'scheduled_at' => Carbon::parse('2024-01-01 10:00:00'),
            'duration_minutes' => 60,
        ]);

        // Create multiple students
        $students = User::factory()->count(3)->create([
            'user_type' => 'student',
            'academy_id' => $this->academy->id,
        ]);

        // Add students to circle
        $circle->students()->attach($students->pluck('id'));

        $reportService = app(StudentReportService::class);
        $reports = $reportService->generateSessionReports($groupSession);

        $this->assertCount(3, $reports);

        // Simulate different attendance patterns for each student
        $attendanceService = app(QuranAttendanceService::class);

        // Student 1: Present (full attendance)
        $attendanceService->trackMeetingEvent($groupSession->id, $students[0]->id, 'join');
        MeetingAttendance::where('session_id', $groupSession->id)
            ->where('user_id', $students[0]->id)
            ->update([
                'first_join_time' => Carbon::parse('2024-01-01 10:00:00'),
                'last_leave_time' => Carbon::parse('2024-01-01 11:00:00'),
                'total_duration_minutes' => 60,
            ]);

        // Student 2: Late (partial attendance)
        $attendanceService->trackMeetingEvent($groupSession->id, $students[1]->id, 'join');
        MeetingAttendance::where('session_id', $groupSession->id)
            ->where('user_id', $students[1]->id)
            ->update([
                'first_join_time' => Carbon::parse('2024-01-01 10:20:00'),
                'last_leave_time' => Carbon::parse('2024-01-01 11:00:00'),
                'total_duration_minutes' => 40,
            ]);

        // Student 3: Absent (no attendance record created)

        // Re-generate reports with attendance data
        $updatedReports = $reportService->generateSessionReports($groupSession);

        $report1 = $updatedReports->where('student_id', $students[0]->id)->first();
        $report2 = $updatedReports->where('student_id', $students[1]->id)->first();
        $report3 = $updatedReports->where('student_id', $students[2]->id)->first();

        $this->assertEquals('present', $report1->attendance_status);
        $this->assertEquals(100, $report1->attendance_percentage);

        $this->assertEquals('late', $report2->attendance_status);
        $this->assertEquals(66.67, round($report2->attendance_percentage, 2));
        $this->assertEquals(20, $report2->late_minutes);

        $this->assertEquals('absent', $report3->attendance_status);
        $this->assertEquals(0, $report3->attendance_percentage);
    }

    public function test_session_statistics_calculation()
    {
        // Create multiple reports with varied performance
        $reports = collect([
            StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => 'present',
                'attendance_percentage' => 95,
                'new_memorization_degree' => 9.0,
                'reservation_degree' => 8.5,
                'connection_quality_score' => 90,
            ]),
        ]);

        // Add more students and reports
        for ($i = 0; $i < 2; $i++) {
            $student = User::factory()->create([
                'user_type' => 'student',
                'academy_id' => $this->academy->id,
            ]);

            StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'student_id' => $student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => $i === 0 ? 'late' : 'absent',
                'attendance_percentage' => $i === 0 ? 75 : 0,
                'new_memorization_degree' => $i === 0 ? 7.5 : null,
                'reservation_degree' => $i === 0 ? 6.5 : null,
                'connection_quality_score' => $i === 0 ? 70 : 0,
            ]);
        }

        $reportService = app(StudentReportService::class);
        $stats = $reportService->getSessionStats($this->session);

        $this->assertEquals(3, $stats['total_students']);
        $this->assertEquals(1, $stats['present_count']);
        $this->assertEquals(1, $stats['late_count']);
        $this->assertEquals(1, $stats['absent_count']);
        $this->assertEquals(0, $stats['partial_count']);

        // Average calculations
        $this->assertEquals(56.67, round($stats['avg_attendance_percentage'], 2));
        $this->assertEquals(8.25, $stats['avg_memorization_degree']); // (9.0 + 7.5) / 2
        $this->assertEquals(7.5, $stats['avg_reservation_degree']); // (8.5 + 6.5) / 2
        $this->assertEquals(53.33, round($stats['avg_connection_quality'], 2));
    }

    public function test_authorization_and_access_control()
    {
        $otherTeacher = User::factory()->create([
            'user_type' => 'quran_teacher',
            'academy_id' => $this->academy->id,
        ]);

        $otherSession = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $otherTeacher->id,
            'session_type' => 'individual',
        ]);

        $report = StudentSessionReport::factory()->create([
            'session_id' => $otherSession->id,
            'teacher_id' => $otherTeacher->id,
            'academy_id' => $this->academy->id,
        ]);

        // Teacher should not be able to access other teacher's reports
        $this->actingAs($this->teacher);

        $response = $this->getJson("/teacher/test-academy/student-reports/{$report->id}");
        $response->assertStatus(404);

        // Teacher should not be able to update evaluation for other teacher's session
        $response = $this->postJson('/teacher/test-academy/student-reports/update', [
            'student_id' => $this->student->id,
            'session_id' => $otherSession->id,
            'new_memorization_degree' => 9.0,
        ]);

        $response->assertStatus(403);
    }

    public function test_data_persistence_and_consistency()
    {
        // Test that multiple service calls maintain data consistency
        $reportService = app(StudentReportService::class);

        // First generation
        $report1 = $reportService->generateStudentReport($this->session, $this->student);
        $initialReportId = $report1->id;

        // Second generation should update, not create new
        $report2 = $reportService->generateStudentReport($this->session, $this->student);

        $this->assertEquals($initialReportId, $report2->id);
        $this->assertEquals(1, StudentSessionReport::where('session_id', $this->session->id)
            ->where('student_id', $this->student->id)
            ->count());

        // Test teacher evaluation persistence
        $evaluatedReport = $reportService->updateTeacherEvaluation(
            $report2,
            8.0,
            7.5,
            'جيد جداً'
        );

        // Verify data persisted correctly
        $fromDb = StudentSessionReport::find($evaluatedReport->id);
        $this->assertEquals(8.0, $fromDb->new_memorization_degree);
        $this->assertEquals(7.5, $fromDb->reservation_degree);
        $this->assertEquals('جيد جداً', $fromDb->notes);
        $this->assertTrue($fromDb->manually_evaluated);
    }

    public function test_error_handling_and_edge_cases()
    {
        $this->actingAs($this->teacher);

        // Test invalid report ID
        $response = $this->getJson('/teacher/test-academy/student-reports/99999');
        $response->assertStatus(404);

        // Test invalid student ID
        $response = $this->getJson('/teacher/test-academy/students/99999/basic-info');
        $response->assertStatus(404);

        // Test invalid evaluation data
        $response = $this->postJson('/teacher/test-academy/student-reports/update', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'new_memorization_degree' => 15, // Invalid: > 10
            'reservation_degree' => -1,      // Invalid: < 0
        ]);

        $response->assertStatus(422);

        // Test missing required fields
        $response = $this->postJson('/teacher/test-academy/student-reports/update', [
            'new_memorization_degree' => 8.0,
            // Missing student_id and session_id
        ]);

        $response->assertStatus(422);
    }
}
