<?php

use App\Models\Academy;
use App\Models\MeetingAttendance;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\StudentReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

describe('StudentReportService', function () {
    beforeEach(function () {
        $this->service = new StudentReportService();
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('generateStudentReport()', function () {
        it('creates a student report with attendance data when meeting attendance exists', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $meetingAttendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(60),
                'total_duration_minutes' => 60,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $session->scheduled_at->toISOString(),
                        'left_at' => $session->scheduled_at->copy()->addMinutes(60)->toISOString(),
                        'duration_minutes' => 60,
                    ],
                ],
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report)->toBeInstanceOf(StudentSessionReport::class)
                ->and($report->session_id)->toBe($session->id)
                ->and($report->student_id)->toBe($this->student->id)
                ->and($report->teacher_id)->toBe($this->teacher->id)
                ->and($report->academy_id)->toBe($this->academy->id)
                ->and($report->attendance_status)->toBe('attended')
                ->and($report->actual_attendance_minutes)->toBe(60)
                ->and($report->is_calculated)->toBeTrue();
        });

        it('creates an absent report when no meeting attendance exists', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report)->toBeInstanceOf(StudentSessionReport::class)
                ->and($report->attendance_status)->toBe('absent')
                ->and($report->actual_attendance_minutes)->toBe(0)
                ->and((float) $report->attendance_percentage)->toBe(0.0)
                ->and($report->meeting_enter_time)->toBeNull()
                ->and($report->meeting_leave_time)->toBeNull()
                ->and($report->is_late)->toBeFalse()
                ->and($report->late_minutes)->toBe(0);
        });

        it('updates existing report if already exists', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $existingReport = StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_status' => 'absent',
                'actual_attendance_minutes' => 0,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(50),
                'total_duration_minutes' => 50,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->id)->toBe($existingReport->id)
                ->and($report->attendance_status)->toBe('attended')
                ->and($report->actual_attendance_minutes)->toBe(50);
        });

        it('marks student as late when joining after scheduled time', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $lateMinutes = 5;
            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at->copy()->addMinutes($lateMinutes),
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(60),
                'total_duration_minutes' => 55,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->late_minutes)->toBe($lateMinutes)
                ->and($report->attendance_status)->toBe('attended');
        });

        it('calculates attendance percentage correctly', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(45),
                'total_duration_minutes' => 45,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect((float) $report->attendance_percentage)->toBe(75.0);
        });

        it('uses transaction for report generation', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            DB::shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $this->service->generateStudentReport($session, $this->student);

            expect(true)->toBeTrue();
        });

        it('stores meeting events from attendance', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $joinLeaveCycles = [
                [
                    'joined_at' => $session->scheduled_at->toISOString(),
                    'left_at' => $session->scheduled_at->copy()->addMinutes(30)->toISOString(),
                    'duration_minutes' => 30,
                ],
                [
                    'joined_at' => $session->scheduled_at->copy()->addMinutes(35)->toISOString(),
                    'left_at' => $session->scheduled_at->copy()->addMinutes(60)->toISOString(),
                    'duration_minutes' => 25,
                ],
            ];

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'total_duration_minutes' => 55,
                'join_leave_cycles' => $joinLeaveCycles,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->meeting_events)->toBeArray()
                ->and($report->meeting_events)->toHaveCount(2);
        });
    });

    describe('calculateAttendanceStatus()', function () {
        it('marks as absent when actual minutes is zero', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('absent');
        });

        it('marks as absent when too late', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $lateMinutes = 20; // More than default 10 minutes
            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at->copy()->addMinutes($lateMinutes),
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(60),
                'total_duration_minutes' => 40,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('absent')
                ->and($report->late_minutes)->toBe($lateMinutes);
        });

        it('marks as absent when attended less than 30 percent', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(15),
                'total_duration_minutes' => 15,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('absent')
                ->and((float) $report->attendance_percentage)->toBe(25.0);
        });

        it('marks as leaved when attended 30-49 percent', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(25),
                'total_duration_minutes' => 25,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('leaved')
                ->and((float) $report->attendance_percentage)->toBeLessThan(50.0)
                ->and((float) $report->attendance_percentage)->toBeGreaterThanOrEqual(30.0);
        });

        it('marks as attended when slightly late but attended well', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at->copy()->addMinutes(5),
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(60),
                'total_duration_minutes' => 55,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('attended')
                ->and($report->is_late)->toBeFalse()
                ->and((float) $report->attendance_percentage)->toBeGreaterThanOrEqual(50.0);
        });

        it('marks as attended when on time and attended 50 percent or more', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at,
                'last_leave_time' => $session->scheduled_at->copy()->addMinutes(50),
                'total_duration_minutes' => 50,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('attended')
                ->and($report->is_late)->toBeFalse()
                ->and((float) $report->attendance_percentage)->toBeGreaterThanOrEqual(50.0);
        });
    });

    describe('getMaxLateMinutes()', function () {
        it('uses default late grace period for group sessions', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'circle_id' => $circle->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'group',
                'first_join_time' => $session->scheduled_at->copy()->addMinutes(5),
                'total_duration_minutes' => 55,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->not->toBe('absent')
                ->and($report->late_minutes)->toBe(5);
        });

        it('uses default late grace period for individual sessions', function () {
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'individual_circle_id' => $individualCircle->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at->copy()->addMinutes(5),
                'total_duration_minutes' => 55,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->not->toBe('absent')
                ->and($report->late_minutes)->toBe(5);
        });

        it('returns default 10 minutes when circle not configured', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'session_type' => 'individual',
                'first_join_time' => $session->scheduled_at->copy()->addMinutes(11),
                'total_duration_minutes' => 49,
            ]);

            $report = $this->service->generateStudentReport($session, $this->student);

            expect($report->attendance_status)->toBe('absent')
                ->and($report->late_minutes)->toBe(11);
        });
    });

    describe('updateTeacherEvaluation()', function () {
        it('updates report with teacher evaluation', function () {
            $report = StudentSessionReport::factory()->create([
                'session_id' => QuranSession::factory()->create(['academy_id' => $this->academy->id])->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'new_memorization_degree' => null,
                'reservation_degree' => null,
                'notes' => null,
            ]);

            $updatedReport = $this->service->updateTeacherEvaluation(
                $report,
                8,
                7,
                'أداء جيد'
            );

            expect((float) $updatedReport->new_memorization_degree)->toBe(8.0)
                ->and((float) $updatedReport->reservation_degree)->toBe(7.0)
                ->and($updatedReport->notes)->toBe('أداء جيد')
                ->and($updatedReport->manually_evaluated)->toBeTrue();
        });

        it('updates evaluated_at timestamp', function () {
            $report = StudentSessionReport::factory()->create([
                'session_id' => QuranSession::factory()->create(['academy_id' => $this->academy->id])->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $updatedReport = $this->service->updateTeacherEvaluation($report, 9, 8);

            expect($updatedReport->evaluated_at)->not->toBeNull();
        });

        it('allows null notes', function () {
            $report = StudentSessionReport::factory()->create([
                'session_id' => QuranSession::factory()->create(['academy_id' => $this->academy->id])->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $updatedReport = $this->service->updateTeacherEvaluation($report, 7, 6, null);

            expect($updatedReport->notes)->toBeNull()
                ->and($updatedReport->manually_evaluated)->toBeTrue();
        });
    });

    describe('generateSessionReports()', function () {
        it('generates reports for all students in a group session', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $students = User::factory()->student()->forAcademy($this->academy)->count(3)->create();
            $circle->students()->attach($students->pluck('id')->mapWithKeys(function ($id) {
                return [$id => ['enrolled_at' => now()]];
            }));

            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'circle_id' => $circle->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $reports = $this->service->generateSessionReports($session);

            expect($reports)->toHaveCount(3)
                ->and($reports->every(fn ($report) => $report instanceof StudentSessionReport))->toBeTrue();
        });

        it('generates report for individual session student', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $reports = $this->service->generateSessionReports($session);

            expect($reports)->toHaveCount(1)
                ->and($reports->first()->student_id)->toBe($this->student->id);
        });

        it('returns empty collection when no students found', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => null,
                'scheduled_at' => Carbon::now()->subMinutes(60),
                'duration_minutes' => 60,
            ]);

            $reports = $this->service->generateSessionReports($session);

            expect($reports)->toBeEmpty();
        });
    });

    describe('getSessionStats()', function () {
        it('returns comprehensive session statistics', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            StudentSessionReport::factory()->count(3)->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $stats = $this->service->getSessionStats($session);

            expect($stats)->toHaveKeys([
                'total_students',
                'attended_count',
                'late_count',
                'absent_count',
                'leaved_count',
                'auto_calculated_count',
                'manually_evaluated_count',
                'avg_attendance_percentage',
                'avg_memorization_degree',
                'avg_reservation_degree',
                'reports',
            ])
                ->and($stats['total_students'])->toBe(4)
                ->and($stats['attended_count'])->toBeGreaterThanOrEqual(1);
        });

        it('calculates average attendance percentage', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_percentage' => 80.0,
            ]);

            StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'attendance_percentage' => 90.0,
            ]);

            $stats = $this->service->getSessionStats($session);

            expect((float) $stats['avg_attendance_percentage'])->toBe(85.0);
        });

        it('calculates average performance degrees', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'new_memorization_degree' => 8.0,
                'reservation_degree' => 7.0,
            ]);

            StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
                'new_memorization_degree' => 9.0,
                'reservation_degree' => 8.0,
            ]);

            $stats = $this->service->getSessionStats($session);

            expect((float) $stats['avg_memorization_degree'])->toBe(8.5)
                ->and((float) $stats['avg_reservation_degree'])->toBe(7.5);
        });

        it('counts auto calculated and manually evaluated reports', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            StudentSessionReport::factory()->autoCalculated()->count(2)->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            StudentSessionReport::factory()->manuallyEvaluated()->count(3)->create([
                'session_id' => $session->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $stats = $this->service->getSessionStats($session);

            expect($stats['auto_calculated_count'])->toBe(2)
                ->and($stats['manually_evaluated_count'])->toBe(3);
        });
    });

    describe('getStudentStats()', function () {
        it('returns comprehensive student statistics', function () {
            $sessions = QuranSession::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            foreach ($sessions as $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats)->toHaveKeys([
                'total_sessions',
                'attended_sessions',
                'missed_sessions',
                'attendance_rate',
                'avg_memorization_degree',
                'avg_reservation_degree',
                'avg_attendance_percentage',
                'latest_report',
                'improvement_trend',
            ])
                ->and($stats['total_sessions'])->toBe(5);
        });

        it('calculates attendance rate correctly', function () {
            $sessions = QuranSession::factory()->count(10)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            foreach ($sessions->take(7) as $session) {
                StudentSessionReport::factory()->attended()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                ]);
            }

            foreach ($sessions->skip(7) as $session) {
                StudentSessionReport::factory()->absent()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['attended_sessions'])->toBe(7)
                ->and($stats['missed_sessions'])->toBe(3)
                ->and((float) $stats['attendance_rate'])->toBe(70.0);
        });

        it('returns latest report', function () {
            $sessions = QuranSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $reports = [];
            foreach ($sessions as $index => $session) {
                $reports[] = StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'created_at' => now()->subDays(3 - $index),
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['latest_report']->id)->toBe($reports[2]->id);
        });

        it('counts late as attended', function () {
            $sessions = QuranSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'session_id' => $sessions[0]->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            StudentSessionReport::factory()->late()->create([
                'session_id' => $sessions[1]->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            StudentSessionReport::factory()->leaved()->create([
                'session_id' => $sessions[2]->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['attended_sessions'])->toBe(3)
                ->and($stats['missed_sessions'])->toBe(0);
        });
    });

    describe('calculateImprovementTrend()', function () {
        it('returns insufficient_data when less than 2 reports', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $report = StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $stats = $this->service->getStudentStats($this->student, collect([$session->id]));

            expect($stats['improvement_trend'])->toBe('insufficient_data');
        });

        it('returns improving when recent performance is better', function () {
            $sessions = QuranSession::factory()->count(10)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            foreach ($sessions->take(5) as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 5.0,
                    'created_at' => now()->subDays(10 - $index),
                ]);
            }

            foreach ($sessions->skip(5) as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 9.0,
                    'created_at' => now()->subDays(4 - $index),
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['improvement_trend'])->toBe('improving');
        });

        it('returns declining when recent performance is worse', function () {
            $sessions = QuranSession::factory()->count(10)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            foreach ($sessions->take(5) as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 9.0,
                    'created_at' => now()->subDays(10 - $index),
                ]);
            }

            foreach ($sessions->skip(5) as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 4.0,
                    'created_at' => now()->subDays(4 - $index),
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['improvement_trend'])->toBe('declining');
        });

        it('returns stable when performance is consistent', function () {
            $sessions = QuranSession::factory()->count(10)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            foreach ($sessions as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 7.5,
                    'created_at' => now()->subDays(10 - $index),
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['improvement_trend'])->toBe('stable');
        });

        it('uses threshold of 0.5 for trend determination', function () {
            $sessions = QuranSession::factory()->count(10)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            foreach ($sessions->take(5) as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 7.0,
                    'created_at' => now()->subDays(10 - $index),
                ]);
            }

            foreach ($sessions->skip(5) as $index => $session) {
                StudentSessionReport::factory()->create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->teacher->id,
                    'academy_id' => $this->academy->id,
                    'new_memorization_degree' => 7.3,
                    'created_at' => now()->subDays(4 - $index),
                ]);
            }

            $stats = $this->service->getStudentStats($this->student, $sessions->pluck('id'));

            expect($stats['improvement_trend'])->toBe('stable');
        });
    });
});
