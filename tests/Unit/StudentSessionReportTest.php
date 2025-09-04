<?php

namespace Tests\Unit;

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSessionReportTest extends TestCase
{
    use RefreshDatabase;

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
            'scheduled_at' => now(),
            'duration_minutes' => 60,
        ]);
    }

    public function test_student_session_report_can_be_created()
    {
        $report = StudentSessionReport::create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'new_memorization_degree' => 8.5,
            'reservation_degree' => 7.0,
            'notes' => 'ممتاز في الحفظ',
            'attendance_status' => 'present',
            'attendance_percentage' => 95.5,
            'is_auto_calculated' => true,
        ]);

        $this->assertInstanceOf(StudentSessionReport::class, $report);
        $this->assertEquals($this->session->id, $report->session_id);
        $this->assertEquals($this->student->id, $report->student_id);
        $this->assertEquals($this->teacher->id, $report->teacher_id);
        $this->assertEquals(8.5, $report->new_memorization_degree);
        $this->assertEquals(7.0, $report->reservation_degree);
        $this->assertEquals('present', $report->attendance_status);
        $this->assertTrue($report->is_auto_calculated);
    }

    public function test_student_session_report_relationships()
    {
        $report = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        // Test relationships
        $this->assertInstanceOf(QuranSession::class, $report->session);
        $this->assertEquals($this->session->id, $report->session->id);

        $this->assertInstanceOf(User::class, $report->student);
        $this->assertEquals($this->student->id, $report->student->id);

        $this->assertInstanceOf(User::class, $report->teacher);
        $this->assertEquals($this->teacher->id, $report->teacher->id);

        $this->assertInstanceOf(Academy::class, $report->academy);
        $this->assertEquals($this->academy->id, $report->academy->id);
    }

    public function test_student_session_report_scopes()
    {
        // Create reports with different attendance statuses
        $presentReport = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'attendance_status' => 'present',
        ]);

        $absentReport = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'attendance_status' => 'absent',
        ]);

        // Test scopes
        $presentReports = StudentSessionReport::present()->get();
        $this->assertTrue($presentReports->contains($presentReport));
        $this->assertFalse($presentReports->contains($absentReport));

        $absentReports = StudentSessionReport::absent()->get();
        $this->assertTrue($absentReports->contains($absentReport));
        $this->assertFalse($absentReports->contains($presentReport));
    }

    public function test_student_session_report_casts()
    {
        $report = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'meeting_enter_time' => '2024-01-01 10:00:00',
            'meeting_leave_time' => '2024-01-01 11:00:00',
            'is_auto_calculated' => true,
            'manually_evaluated' => false,
            'meeting_events' => ['join' => '10:00', 'leave' => '11:00'],
        ]);

        // Test date casts
        $this->assertInstanceOf(Carbon::class, $report->meeting_enter_time);
        $this->assertInstanceOf(Carbon::class, $report->meeting_leave_time);
        $this->assertInstanceOf(Carbon::class, $report->evaluated_at);

        // Test boolean casts
        $this->assertIsBool($report->is_auto_calculated);
        $this->assertIsBool($report->manually_evaluated);
        $this->assertIsBool($report->is_late);

        // Test array cast
        $this->assertIsArray($report->meeting_events);
        $this->assertEquals(['join' => '10:00', 'leave' => '11:00'], $report->meeting_events);
    }

    public function test_student_session_report_academy_scope()
    {
        $otherAcademy = Academy::factory()->create();

        $reportInAcademy = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $reportInOtherAcademy = StudentSessionReport::factory()->create([
            'academy_id' => $otherAcademy->id,
        ]);

        // Mock academy helper
        config(['app.current_academy_id' => $this->academy->id]);

        $reportsInCurrentAcademy = StudentSessionReport::forCurrentAcademy()->get();

        $this->assertTrue($reportsInCurrentAcademy->contains($reportInAcademy));
        $this->assertFalse($reportsInCurrentAcademy->contains($reportInOtherAcademy));
    }

    public function test_attendance_status_in_arabic_accessor()
    {
        $statuses = [
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'partial' => 'حضور جزئي',
        ];

        foreach ($statuses as $status => $arabic) {
            $report = StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => $status,
            ]);

            $this->assertEquals($arabic, $report->attendance_status_in_arabic);
        }
    }

    public function test_connection_quality_grade_accessor()
    {
        $qualities = [
            100 => 'ممتاز',
            85 => 'جيد جداً',
            70 => 'جيد',
            50 => 'مقبول',
            30 => 'ضعيف',
        ];

        foreach ($qualities as $score => $grade) {
            $report = StudentSessionReport::factory()->create([
                'session_id' => $this->session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'connection_quality_score' => $score,
            ]);

            $this->assertEquals($grade, $report->connection_quality_grade);
        }
    }

    public function test_is_late_based_on_minutes()
    {
        $lateReport = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'late_minutes' => 15,
        ]);

        $onTimeReport = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'late_minutes' => 0,
        ]);

        $this->assertTrue($lateReport->is_late);
        $this->assertFalse($onTimeReport->is_late);
    }

    public function test_performance_grade_calculation()
    {
        $report = StudentSessionReport::factory()->create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'new_memorization_degree' => 8.5,
            'reservation_degree' => 7.0,
        ]);

        $expectedAverage = (8.5 + 7.0) / 2;
        $this->assertEquals($expectedAverage, $report->average_performance_degree);
    }

    public function test_validation_rules()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Test required fields
        StudentSessionReport::create([
            // Missing required fields
        ]);
    }
}
