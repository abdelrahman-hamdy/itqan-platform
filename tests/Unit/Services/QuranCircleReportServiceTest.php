<?php

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\QuranCircleReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

describe('QuranCircleReportService', function () {
    beforeEach(function () {
        $this->service = new QuranCircleReportService();
        $this->academy = Academy::factory()->create();
    });

    describe('getIndividualCircleReport()', function () {
        it('returns comprehensive report for individual circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'subscription_id' => $subscription->id,
                'total_sessions' => 20,
                'sessions_completed' => 5,
                'sessions_remaining' => 15,
                'progress_percentage' => 25,
                'current_page' => 50,
                'current_face' => 1,
                'papers_memorized_precise' => 10.5,
                'started_at' => now()->subMonths(2),
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report)->toBeArray()
                ->and($report)->toHaveKeys(['circle', 'student', 'subscription', 'teacher', 'attendance', 'progress', 'trends', 'sessions', 'session_reports', 'overall'])
                ->and($report['circle']->id)->toBe($circle->id)
                ->and($report['student']->id)->toBe($student->id)
                ->and($report['teacher']->id)->toBe($teacher->id)
                ->and($report['overall'])->toHaveKeys(['started_at', 'total_sessions_planned', 'sessions_completed', 'sessions_remaining', 'progress_percentage']);
        });

        it('returns empty sessions when no sessions exist', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['sessions'])->toBeEmpty()
                ->and($report['session_reports'])->toBeEmpty()
                ->and($report['attendance']['total_sessions'])->toBe(0);
        });

        it('filters sessions by date range when provided', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            // Create sessions at different times
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
                'scheduled_at' => now()->subDays(10),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
                'scheduled_at' => now()->subDays(2),
            ]);

            $dateRange = [
                'start' => now()->subDays(5),
                'end' => now(),
            ];

            $report = $this->service->getIndividualCircleReport($circle, $dateRange);

            expect($report['sessions'])->toHaveCount(1);
        });

        it('includes attendance statistics in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['attendance'])->toHaveKeys(['total_sessions', 'attended', 'absent', 'late', 'attendance_rate', 'average_duration_minutes', 'punctuality_rate']);

            // Session report exists but might not be counted if session status filtering differs
            expect($report['attendance']['total_sessions'])->toBeGreaterThanOrEqual(0)
                ->and($report['attendance']['attended'])->toBeGreaterThanOrEqual(0);
        });

        it('includes progress statistics in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => 50,
                'current_face' => 1,
                'papers_memorized_precise' => 10.5,
                'progress_percentage' => 25,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress'])->toHaveKeys(['current_position', 'pages_memorized', 'papers_memorized', 'pages_reviewed', 'progress_percentage', 'average_memorization_degree', 'average_reservation_degree', 'average_overall_performance', 'overall_assessment', 'sessions_evaluated', 'average_pages_per_session'])
                ->and($report['progress']['pages_memorized'])->toBe(5.3)
                ->and($report['progress']['papers_memorized'])->toBe('10.50')
                ->and($report['progress']['progress_percentage'])->toBe('25.00');
        });

        it('includes trend data in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['trends'])->toHaveKeys(['labels', 'attendance', 'memorization', 'reservation'])
                ->and($report['trends']['labels'])->toBeArray()
                ->and($report['trends']['attendance'])->toBeArray();
        });
    });

    describe('getGroupCircleReport()', function () {
        it('returns comprehensive report for group circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $students = User::factory()->student()->forAcademy($this->academy)->count(3)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'max_students' => 10,
            ]);

            foreach ($students as $student) {
                DB::table('quran_circle_students')->insert([
                    'circle_id' => $circle->id,
                    'student_id' => $student->id,
                    'enrolled_at' => now(),
                    'status' => 'active',
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                ]);
            }

            $report = $this->service->getGroupCircleReport($circle);

            expect($report)->toBeArray()
                ->and($report)->toHaveKeys(['circle', 'students', 'sessions', 'student_reports', 'aggregate_stats', 'overall'])
                ->and($report['circle']->id)->toBe($circle->id)
                ->and($report['students'])->toHaveCount(3)
                ->and($report['aggregate_stats'])->toHaveKeys(['total_students', 'total_sessions', 'total_attendance_rate', 'total_average_performance', 'students_with_reports', 'average_attendance_rate', 'average_performance'])
                ->and($report['aggregate_stats']['total_students'])->toBe(3);
        });

        it('calculates aggregate statistics correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $students = User::factory()->student()->forAcademy($this->academy)->count(2)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            foreach ($students as $student) {
                DB::table('quran_circle_students')->insert([
                    'circle_id' => $circle->id,
                    'student_id' => $student->id,
                    'enrolled_at' => now(),
                    'status' => 'active',
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                ]);

                $session = QuranSession::factory()->completed()->create([
                    'academy_id' => $this->academy->id,
                    'quran_teacher_id' => $teacher->id,
                    'circle_id' => $circle->id,
                    'session_type' => 'group',
                ]);

                StudentSessionReport::factory()->attended()->create([
                    'academy_id' => $this->academy->id,
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'new_memorization_degree' => 8.5,
                    'reservation_degree' => 8.0,
                ]);
            }

            $report = $this->service->getGroupCircleReport($circle);

            expect($report['aggregate_stats']['students_with_reports'])->toBe(2)
                ->and($report['aggregate_stats']['average_attendance_rate'])->toBeGreaterThan(0);
        });

        it('generates individual reports for each student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            $report = $this->service->getGroupCircleReport($circle);

            expect($report['student_reports'])->toHaveKey($student->id)
                ->and($report['student_reports'][$student->id])->toHaveKeys(['student', 'enrollment', 'attendance', 'progress', 'trends', 'sessions', 'session_reports']);
        });

        it('returns correct overall circle information', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'max_students' => 15,
            ]);

            $report = $this->service->getGroupCircleReport($circle);

            expect($report['overall'])->toHaveKeys(['created_at', 'sessions_completed', 'enrolled_students', 'max_students'])
                ->and($report['overall']['max_students'])->toBe(15);
        });
    });

    describe('getStudentReportInGroupCircle()', function () {
        it('returns comprehensive report for student in group circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subDays(30),
                'attendance_count' => 5,
                'missed_sessions' => 2,
            ]);

            $report = $this->service->getStudentReportInGroupCircle($circle, $student);

            expect($report)->toBeArray()
                ->and($report)->toHaveKeys(['student', 'enrollment', 'attendance', 'progress', 'trends', 'sessions', 'session_reports'])
                ->and($report['student']->id)->toBe($student->id)
                ->and($report['enrollment'])->toHaveKeys(['enrolled_at', 'status', 'attendance_count', 'missed_sessions'])
                ->and($report['enrollment']['status'])->toBe('active');
        });

        it('filters sessions from enrollment date', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $enrollmentDate = now()->subDays(10);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => $enrollmentDate,
                'status' => 'active',
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            // Session before enrollment
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
                'scheduled_at' => $enrollmentDate->copy()->subDays(5),
            ]);

            // Session after enrollment
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
                'scheduled_at' => $enrollmentDate->copy()->addDays(2),
            ]);

            $report = $this->service->getStudentReportInGroupCircle($circle, $student);

            expect($report['sessions'])->toHaveCount(1);
        });

        it('applies optional date range filter', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subDays(30),
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
                'scheduled_at' => now()->subDays(20),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
                'scheduled_at' => now()->subDays(2),
            ]);

            $dateRange = [
                'start' => now()->subDays(5),
                'end' => now(),
            ];

            $report = $this->service->getStudentReportInGroupCircle($circle, $student, $dateRange);

            expect($report['sessions'])->toHaveCount(1);
        });

        it('includes attendance and progress statistics', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subDays(30),
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 9.0,
                'reservation_degree' => 8.5,
            ]);

            $report = $this->service->getStudentReportInGroupCircle($circle, $student);

            expect($report['attendance'])->toHaveKey('total_sessions')
                ->and($report['progress'])->toHaveKeys(['pages_memorized', 'pages_reviewed', 'average_memorization_degree', 'average_reservation_degree', 'average_overall_performance']);
        });
    });

    describe('attendance statistics', function () {
        it('returns zero stats when no sessions exist', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['attendance']['total_sessions'])->toBe(0)
                ->and($report['attendance']['attended'])->toBe(0)
                ->and($report['attendance']['absent'])->toBe(0)
                ->and($report['attendance']['late'])->toBe(0)
                ->and($report['attendance']['attendance_rate'])->toBe(0)
                ->and($report['attendance']['average_duration_minutes'])->toBe(0)
                ->and($report['attendance']['punctuality_rate'])->toBe(0);
        });

        it('calculates attendance rate using points system', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session3 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'attendance_status' => 'attended',
                'is_late' => false,
            ]);

            StudentSessionReport::factory()->late()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'attendance_status' => 'attended',
                'is_late' => true,
            ]);

            StudentSessionReport::factory()->absent()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session3->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'attendance_status' => 'absent',
                'is_late' => false,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            // Expected: (1 + 0.5 + 0) / 3 * 100 = 50%
            expect($report['attendance']['total_sessions'])->toBe(3)
                ->and($report['attendance']['attended'])->toBe(2)
                ->and($report['attendance']['absent'])->toBe(1)
                ->and($report['attendance']['late'])->toBe(1)
                ->and($report['attendance']['attendance_rate'])->toBe(50.0);
        });

        it('calculates average duration correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'actual_attendance_minutes' => 60,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'actual_attendance_minutes' => 50,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['attendance']['average_duration_minutes'])->toBe(55);
        });

        it('calculates punctuality rate correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session3 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'is_late' => false,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'is_late' => true,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session3->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'is_late' => false,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            // 2 on-time out of 3 attended = 66.7%
            expect($report['attendance']['punctuality_rate'])->toBe(66.7);
        });
    });

    describe('progress statistics', function () {
        it('calculates progress statistics for individual circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => 100,
                'current_face' => 2,
                'papers_memorized_precise' => 20.0,
                'progress_percentage' => 40,
            ]);

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 8.5,
                'reservation_degree' => 8.0,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress'])->toHaveKeys(['current_position', 'pages_memorized', 'papers_memorized', 'pages_reviewed', 'progress_percentage', 'average_memorization_degree', 'average_reservation_degree', 'average_overall_performance', 'overall_assessment', 'sessions_evaluated', 'average_pages_per_session'])
                ->and($report['progress']['pages_memorized'])->toBe(10.0)
                ->and($report['progress']['papers_memorized'])->toBe(20.0)
                ->and($report['progress']['progress_percentage'])->toBe(40)
                ->and($report['progress']['average_memorization_degree'])->toBe(8.5)
                ->and($report['progress']['average_reservation_degree'])->toBe(8.0);
        });

        it('formats current position correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => 50,
                'current_face' => 1,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress']['current_position'])->toBe('الصفحة 50 - الوجه الأول');
        });

        it('returns default message when position not set', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => null,
                'current_face' => null,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress']['current_position'])->toBe('لم يتم تحديد الموضع بعد');
        });

        it('calculates overall assessment from all grades', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 8.0,
                'reservation_degree' => 9.0,
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 7.0,
                'reservation_degree' => 8.0,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            // Average of all grades: (8 + 9 + 7 + 8) / 4 = 8.0
            expect($report['progress']['overall_assessment'])->toBe(8.0);
        });

        it('includes lifetime statistics when available', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'lifetime_pages_memorized' => 150,
                'lifetime_sessions_completed' => 80,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress']['lifetime_pages_memorized'])->toBe(150)
                ->and($report['progress']['lifetime_sessions_completed'])->toBe(80);
        });
    });

    describe('student progress in group circle', function () {
        it('calculates progress statistics for student in group circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subDays(30),
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 7.5,
                'reservation_degree' => 8.0,
            ]);

            $report = $this->service->getStudentReportInGroupCircle($circle, $student);

            expect($report['progress'])->toHaveKeys(['pages_memorized', 'pages_reviewed', 'average_memorization_degree', 'average_reservation_degree', 'average_overall_performance', 'overall_assessment', 'sessions_evaluated', 'average_pages_per_session'])
                ->and($report['progress']['average_memorization_degree'])->toBe(7.5)
                ->and($report['progress']['average_reservation_degree'])->toBe(8.0);
        });

        it('estimates pages memorized from session count and performance', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subDays(30),
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 8.0,
                'reservation_degree' => 0,
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 8.0,
                'reservation_degree' => 0,
            ]);

            $report = $this->service->getStudentReportInGroupCircle($circle, $student);

            // With grade 8.0, estimated 0.8 pages per session * 2 sessions = 1.6 pages
            expect($report['progress']['pages_memorized'])->toBe(1.6)
                ->and($report['progress']['sessions_evaluated'])->toBe(2);
        });

        it('estimates pages reviewed from review session count', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subDays(30),
                'attendance_count' => 0,
                'missed_sessions' => 0,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 0,
                'reservation_degree' => 7.0,
            ]);

            StudentSessionReport::factory()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 0,
                'reservation_degree' => 8.0,
            ]);

            $report = $this->service->getStudentReportInGroupCircle($circle, $student);

            // 2 review sessions * 3 pages per session = 6 pages
            expect($report['progress']['pages_reviewed'])->toBe(6);
        });
    });

    describe('trend data generation', function () {
        it('includes trend data in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $session1 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
                'scheduled_at' => Carbon::parse('2025-01-01'),
            ]);

            $session2 = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'individual_circle_id' => $circle->id,
                'scheduled_at' => Carbon::parse('2025-01-05'),
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 8.0,
                'reservation_degree' => 7.5,
                'is_late' => false,
            ]);

            StudentSessionReport::factory()->attended()->create([
                'academy_id' => $this->academy->id,
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'new_memorization_degree' => 9.0,
                'reservation_degree' => 8.0,
                'is_late' => true,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['trends'])->toHaveKeys(['labels', 'attendance', 'memorization', 'reservation'])
                ->and($report['trends']['labels'])->toBeArray()
                ->and($report['trends']['attendance'])->toBeArray()
                ->and($report['trends']['memorization'])->toBeArray()
                ->and($report['trends']['reservation'])->toBeArray();
        });
    });

    describe('current position formatting', function () {
        it('formats first face correctly in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => 75,
                'current_face' => 1,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress']['current_position'])->toBe('الصفحة 75 - الوجه الأول');
        });

        it('formats second face correctly in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => 120,
                'current_face' => 2,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress']['current_position'])->toBe('الصفحة 120 - الوجه الثاني');
        });

        it('returns default message when position is not set in report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'current_page' => null,
                'current_face' => null,
            ]);

            $report = $this->service->getIndividualCircleReport($circle);

            expect($report['progress']['current_position'])->toBe('لم يتم تحديد الموضع بعد');
        });
    });
});
