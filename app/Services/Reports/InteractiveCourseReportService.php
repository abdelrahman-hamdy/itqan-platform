<?php

namespace App\Services\Reports;

use App\DTOs\Reports\AttendanceDTO;
use App\DTOs\Reports\PerformanceDTO;
use App\DTOs\Reports\StatDTO;
use App\DTOs\Reports\StudentReportRowDTO;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\InteractiveCourse;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interactive Course Report Service
 *
 * Handles report generation for interactive courses including
 * course-level statistics and individual student reports.
 */
class InteractiveCourseReportService extends BaseReportService
{
    /**
     * Get comprehensive course overview report
     *
     * @return array Report data with DTOs
     */
    public function getCourseOverviewReport(InteractiveCourse $course): array
    {
        $sessions = $course->sessions;
        $enrollments = $course->enrollments()->with('student.user')->get();

        return [
            'course' => $course,
            'attendance' => $this->calculateCourseAttendance($sessions, $enrollments),
            'performance' => $this->calculateCoursePerformance($sessions, $enrollments),
            'progress' => $this->calculateCourseProgress($course, $sessions),
            'studentRows' => $this->generateStudentRows($course, $enrollments),
            'statsCards' => $this->generateStatsCards($course, $sessions, $enrollments),
        ];
    }

    /**
     * Get individual student report within course
     *
     * @param  User|\App\Models\StudentProfile  $student
     * @return array Report data with DTOs
     */
    public function getStudentReport(InteractiveCourse $course, $student, ?array $dateRange = null): array
    {
        $sessions = $dateRange
            ? $this->filterSessionsByDateRange($course->sessions, $dateRange)
            : $course->sessions;

        $studentReports = $this->getStudentReportsForCourse($sessions, $student);

        return [
            'course' => $course,
            'student' => $student,
            'attendance' => $this->calculateStudentAttendance($studentReports, $sessions),
            'performance' => $this->calculateStudentPerformance($studentReports),
            'progress' => $this->calculateStudentProgress($studentReports, $course),
            'sessions' => $sessions,
        ];
    }

    /**
     * Calculate course-level attendance statistics
     */
    protected function calculateCourseAttendance(Collection $sessions, Collection $enrollments): AttendanceDTO
    {
        $completedSessions = $sessions->filter(function ($session) {
            $status = $this->normalizeAttendanceStatus($session->status ?? '');

            return $status === SessionStatus::COMPLETED->value;
        })->count();

        $allReports = $sessions->flatMap(function ($session) {
            return $session->studentReports ?? collect();
        });

        $totalAttendanceRecords = $allReports->count();
        $attendedRecords = $allReports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return in_array($status, [AttendanceStatus::ATTENDED->value, 'present', AttendanceStatus::LATE->value]);
        })->count();

        $lateRecords = $allReports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::LATE->value;
        })->count();

        $attendanceRate = $totalAttendanceRecords > 0
            ? round(($attendedRecords / $totalAttendanceRecords) * 100, 2)
            : 0;

        return new AttendanceDTO(
            totalSessions: $completedSessions,
            attended: $attendedRecords,
            absent: $totalAttendanceRecords - $attendedRecords,
            late: $lateRecords,
            attendanceRate: $attendanceRate,
        );
    }

    /**
     * Calculate course-level performance statistics
     */
    protected function calculateCoursePerformance(Collection $sessions, Collection $enrollments): PerformanceDTO
    {
        $allReports = $sessions->flatMap(function ($session) {
            return $session->studentReports ?? collect();
        });

        $avgHomework = $allReports->whereNotNull('homework_degree')->avg('homework_degree') ?? 0;
        $totalEvaluated = $allReports->whereNotNull('homework_degree')->count();

        return PerformanceDTO::fromInteractiveData([
            'average_overall_performance' => $avgHomework,
            'average_homework_degree' => $avgHomework,
            'sessions_evaluated' => $totalEvaluated,
        ]);
    }

    /**
     * Calculate course progress
     *
     * @return array Progress data
     */
    protected function calculateCourseProgress(InteractiveCourse $course, Collection $sessions): array
    {
        $enrolledStudents = $course->enrollments()->count();

        $completedSessions = $sessions->filter(function ($session) {
            $status = $this->normalizeAttendanceStatus($session->status ?? '');

            return $status === SessionStatus::COMPLETED->value;
        })->count();

        return [
            'enrolled_students' => $enrolledStudents,
            'sessions_completed' => $completedSessions,
            'total_sessions' => $sessions->count(),
        ];
    }

    /**
     * Generate student rows for table display
     *
     * @return Collection Collection of StudentReportRowDTO
     */
    protected function generateStudentRows(InteractiveCourse $course, Collection $enrollments): Collection
    {
        return $enrollments->map(function ($enrollment) use ($course) {
            $student = $enrollment->student;
            $studentUser = $student?->user;

            if (! $studentUser) {
                return null;
            }

            $studentReports = $this->getStudentReportsForCourse($course->sessions, $student);

            $completedSessions = $course->sessions->filter(function ($session) {
                $status = $this->normalizeAttendanceStatus($session->status ?? '');

                return $status === SessionStatus::COMPLETED->value;
            })->count();

            $attendedSessions = $studentReports->filter(function ($report) {
                $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

                return in_array($status, [AttendanceStatus::ATTENDED->value, 'present', AttendanceStatus::LATE->value]);
            })->count();

            $attendanceRate = $completedSessions > 0
                ? round(($attendedSessions / $completedSessions) * 100)
                : 0;

            $avgPerformance = $studentReports->whereNotNull('homework_degree')->avg('homework_degree') ?? 0;

            return new StudentReportRowDTO(
                studentId: $studentUser->id,
                studentName: $studentUser->name,
                enrollmentDate: $enrollment->created_at?->format('Y-m-d'),
                attendanceRate: $attendanceRate,
                performanceScore: round($avgPerformance, 1),
                completedSessions: $attendedSessions,
                detailUrl: route('teacher.interactive-courses.student-report', [
                    'subdomain' => $this->getAcademySubdomain(),
                    'course' => $course->id,
                    'student' => $studentUser->id,
                ])
            );
        })->filter();
    }

    /**
     * Generate stats cards for overview
     *
     * @return array Array of StatDTO
     */
    protected function generateStatsCards(InteractiveCourse $course, Collection $sessions, Collection $enrollments): array
    {
        $completedSessions = $sessions->filter(function ($session) {
            $status = $this->normalizeAttendanceStatus($session->status ?? '');

            return $status === SessionStatus::COMPLETED->value;
        })->count();

        $attendance = $this->calculateCourseAttendance($sessions, $enrollments);
        $performance = $this->calculateCoursePerformance($sessions, $enrollments);

        return [
            new StatDTO(
                label: 'الطلاب المسجلين',
                value: $enrollments->count(),
                color: 'blue',
                icon: 'ri-group-line'
            ),
            new StatDTO(
                label: 'الجلسات المكتملة',
                value: $completedSessions,
                color: 'green',
                icon: 'ri-calendar-check-line'
            ),
            new StatDTO(
                label: 'متوسط نسبة الحضور',
                value: number_format($attendance->attendanceRate, 0).'%',
                color: 'purple',
                icon: 'ri-user-star-line'
            ),
            new StatDTO(
                label: 'متوسط الأداء',
                value: number_format($performance->averageOverall, 1).'/10',
                color: 'yellow',
                icon: 'ri-star-line'
            ),
        ];
    }

    /**
     * Get student reports for a course
     *
     * @param  User|\App\Models\StudentProfile  $student
     */
    protected function getStudentReportsForCourse(Collection $sessions, $student): Collection
    {
        return $sessions->flatMap(function ($session) use ($student) {
            $reports = $session->studentReports ?? collect();

            return $reports->where('student_id', $student->id);
        });
    }

    /**
     * Calculate student attendance from reports
     */
    protected function calculateStudentAttendance(Collection $reports, Collection $sessions): AttendanceDTO
    {
        $completedSessions = $sessions->filter(function ($session) {
            $status = $this->normalizeAttendanceStatus($session->status ?? '');

            return $status === SessionStatus::COMPLETED->value;
        })->count();

        return $this->calculateAttendanceFromReports($reports, $completedSessions);
    }

    /**
     * Calculate student performance from reports
     */
    protected function calculateStudentPerformance(Collection $reports): PerformanceDTO
    {
        $avgHomework = $reports->whereNotNull('homework_degree')->avg('homework_degree') ?? 0;
        $totalEvaluated = $reports->whereNotNull('homework_degree')->count();

        return PerformanceDTO::fromInteractiveData([
            'average_overall_performance' => $avgHomework,
            'average_homework_degree' => $avgHomework,
            'sessions_evaluated' => $totalEvaluated,
        ]);
    }

    /**
     * Calculate student progress
     *
     * @return array Progress data
     */
    protected function calculateStudentProgress(Collection $reports, InteractiveCourse $course): array
    {
        $attendedSessions = $reports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return in_array($status, [AttendanceStatus::ATTENDED->value, 'present', AttendanceStatus::LATE->value]);
        })->count();

        $totalSessions = $course->sessions->count();
        $completionRate = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0;

        return [
            'sessions_completed' => $attendedSessions,
            'total_sessions' => $totalSessions,
            'completion_rate' => $completionRate,
        ];
    }
}
