<?php

namespace App\Services\Reports;

use App\DTOs\Reports\AttendanceDTO;
use App\DTOs\Reports\PerformanceDTO;
use App\DTOs\Reports\ProgressDTO;
use App\DTOs\Reports\StatDTO;
use App\DTOs\Reports\TrendDataDTO;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranCircleStudent;
use App\Models\QuranIndividualCircle;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Quran Report Service
 *
 * Handles comprehensive report generation for Quran circles (individual and group)
 * including attendance, progress, homework, and performance metrics.
 *
 * REFACTORED: Now uses DTOs instead of raw arrays for type safety and consistency.
 */
class QuranReportService extends BaseReportService
{
    /**
     * Generate comprehensive report for individual circle
     *
     * @param  array|null  $dateRange  Optional date range filter ['start' => Carbon, 'end' => Carbon]
     * @return array Report data with DTOs
     */
    public function getIndividualCircleReport(QuranIndividualCircle $circle, ?array $dateRange = null): array
    {
        $student = $circle->student;

        // Build sessions query with optional date filter
        $sessionsQuery = $circle->sessions()->orderBy('scheduled_at', 'desc');

        if ($dateRange) {
            $sessionsQuery->whereBetween('scheduled_at', [$dateRange['start'], $dateRange['end']]);
        }

        $sessions = $sessionsQuery->get();
        $completedSessions = $sessions->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ABSENT->value]);

        // Get student session reports using Eloquent for tenant scoping
        $sessionReports = StudentSessionReport::whereIn('session_id', $sessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get();

        // Calculate all metrics using DTOs
        $attendanceDTO = $this->calculateQuranAttendance($completedSessions, $sessionReports);
        $performanceDTO = $this->calculateQuranPerformance($circle, $completedSessions, $sessionReports);
        $progressDTO = $this->calculateQuranProgress($circle, $completedSessions, $sessionReports);
        $trendsDTO = $this->generateQuranTrendData($completedSessions, $sessionReports);
        $statsCards = $this->generateIndividualStatsCards($circle, $attendanceDTO, $performanceDTO, $progressDTO);

        return [
            'circle' => $circle,
            'student' => $student,
            'subscription' => $circle->subscription,
            'teacher' => $circle->quranTeacher,

            // DTOs
            'attendance' => $attendanceDTO,
            'performance' => $performanceDTO,
            'progress' => $progressDTO,
            'trends' => $trendsDTO,
            'statsCards' => $statsCards,

            // Overall circle info
            'overall' => [
                'started_at' => $circle->started_at,
                'total_sessions_planned' => $circle->total_sessions,
                'sessions_completed' => $circle->sessions_completed,
                'sessions_remaining' => $circle->sessions_remaining,
                'progress_percentage' => $circle->progress_percentage ?? 0,
            ],
        ];
    }

    /**
     * Generate comprehensive report for group circle (all students)
     *
     * @return array Report data with aggregate statistics
     */
    public function getGroupCircleReport(QuranCircle $circle): array
    {
        $students = $circle->students;
        $sessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();

        // Generate individual reports for each student
        $studentReports = [];
        $aggregateStats = [
            'total_students' => $students->count(),
            'total_sessions' => $sessions->count(),
            'total_attendance_rate' => 0,
            'total_average_performance' => 0,
            'students_with_reports' => 0,
        ];

        foreach ($students as $student) {
            $report = $this->getStudentReportInGroupCircle($circle, $student);
            $studentReports[$student->id] = $report;

            // Aggregate for overall stats
            if ($report['attendance']->totalSessions > 0) {
                $aggregateStats['total_attendance_rate'] += $report['attendance']->attendanceRate;
                $aggregateStats['students_with_reports']++;
            }

            if ($report['performance']->averageOverall > 0) {
                $aggregateStats['total_average_performance'] += $report['performance']->averageOverall;
            }
        }

        // Calculate averages
        if ($aggregateStats['students_with_reports'] > 0) {
            $aggregateStats['average_attendance_rate'] = round($aggregateStats['total_attendance_rate'] / $aggregateStats['students_with_reports'], 1);
            $aggregateStats['average_performance'] = round($aggregateStats['total_average_performance'] / $aggregateStats['students_with_reports'], 1);
        } else {
            $aggregateStats['average_attendance_rate'] = 0;
            $aggregateStats['average_performance'] = 0;
        }

        return [
            'circle' => $circle,
            'students' => $students,
            'sessions' => $sessions,
            'student_reports' => $studentReports,
            'aggregate_stats' => $aggregateStats,

            // Overall circle info
            'overall' => [
                'created_at' => $circle->created_at,
                'sessions_completed' => $circle->sessions_completed ?? $sessions->whereIn('status', [SessionStatus::COMPLETED->value])->count(),
                'enrolled_students' => $students->count(),
                'max_students' => $circle->max_students,
            ],
        ];
    }

    /**
     * Generate report for specific student in group circle
     *
     * @param  array|null  $dateRange  Optional date range filter
     * @return array Report data with DTOs
     */
    public function getStudentReportInGroupCircle(QuranCircle $circle, User $student, ?array $dateRange = null): array
    {
        // Get student's enrollment via relationship for proper tenant scoping
        // Using the circle's students() relationship gives us pivot data
        $enrolledStudent = $circle->students()->where('users.id', $student->id)->first();

        $enrolledAt = $enrolledStudent?->pivot?->enrolled_at
            ? \Carbon\Carbon::parse($enrolledStudent->pivot->enrolled_at)
            : null;

        // Get sessions since student enrollment with optional date filter
        $allSessions = $circle->sessions()
            ->when($enrolledAt, function ($query) use ($enrolledAt) {
                return $query->where('scheduled_at', '>=', $enrolledAt);
            })
            ->when($dateRange, function ($query) use ($dateRange) {
                return $query->whereBetween('scheduled_at', [$dateRange['start'], $dateRange['end']]);
            })
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $completedSessions = $allSessions->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ABSENT->value]);

        // Get student's session reports using Eloquent for tenant scoping
        $sessionReports = StudentSessionReport::whereIn('session_id', $allSessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get();

        // Calculate metrics using DTOs
        $attendanceDTO = $this->calculateQuranAttendance($completedSessions, $sessionReports);
        $performanceDTO = $this->calculateGroupStudentPerformance($student, $completedSessions, $sessionReports);
        $trendsDTO = $this->generateQuranTrendData($completedSessions, $sessionReports);

        return [
            'student' => $student,
            'enrollment' => [
                'enrolled_at' => $enrolledAt,
                'status' => $enrolledStudent?->pivot?->status ?? 'active',
                'attendance_count' => $enrolledStudent?->pivot?->attendance_count ?? 0,
                'missed_sessions' => $enrolledStudent?->pivot?->missed_sessions ?? 0,
            ],

            // DTOs
            'attendance' => $attendanceDTO,
            'performance' => $performanceDTO,
            'trends' => $trendsDTO,
        ];
    }

    /**
     * Calculate Quran attendance with points-based system
     *
     * Points: attended=1, late=0.5, absent=0
     */
    protected function calculateQuranAttendance(Collection $completedSessions, Collection $sessionReports): AttendanceDTO
    {
        $totalSessions = $completedSessions->count();

        if ($totalSessions === 0) {
            return new AttendanceDTO(
                totalSessions: 0,
                attended: 0,
                absent: 0,
                late: 0,
                attendanceRate: 0,
                averageDurationMinutes: 0
            );
        }

        $attended = $sessionReports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::ATTENDED->value;
        })->count();

        $absent = $sessionReports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::ABSENT->value;
        })->count();

        $late = $sessionReports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::LATE->value;
        })->count();

        $avgDuration = $sessionReports->where('attendance_status', AttendanceStatus::ATTENDED->value)
            ->avg('actual_attendance_minutes') ?? 0;

        // Calculate attendance using points system
        $attendanceRate = $this->calculatePointsBasedAttendanceRate($sessionReports);

        return new AttendanceDTO(
            totalSessions: $totalSessions,
            attended: $attended,
            absent: $absent,
            late: $late,
            attendanceRate: $attendanceRate,
            averageDurationMinutes: (int) round($avgDuration)
        );
    }

    /**
     * Calculate Quran performance for individual circle
     */
    protected function calculateQuranPerformance(
        QuranIndividualCircle $circle,
        Collection $completedSessions,
        Collection $sessionReports
    ): PerformanceDTO {
        $avgMemorization = $sessionReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?? 0;
        $avgReservation = $sessionReports->whereNotNull('reservation_degree')->avg('reservation_degree') ?? 0;

        // Calculate overall performance (average of both)
        $overallPerformance = 0;
        $count = 0;
        if ($avgMemorization > 0) {
            $overallPerformance += $avgMemorization;
            $count++;
        }
        if ($avgReservation > 0) {
            $overallPerformance += $avgReservation;
            $count++;
        }
        $averageOverall = $count > 0 ? round($overallPerformance / $count, 1) : 0;

        $totalEvaluated = $sessionReports->whereNotNull('new_memorization_degree')->count();

        return PerformanceDTO::fromQuranData([
            'average_overall_performance' => $averageOverall,
            'average_memorization_degree' => round($avgMemorization, 1),
            'average_reservation_degree' => round($avgReservation, 1),
            'sessions_evaluated' => $totalEvaluated,
        ]);
    }

    /**
     * Calculate performance for student in group circle
     */
    protected function calculateGroupStudentPerformance(
        User $student,
        Collection $completedSessions,
        Collection $sessionReports
    ): PerformanceDTO {
        $avgMemorization = $sessionReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?? 0;
        $avgReservation = $sessionReports->whereNotNull('reservation_degree')->avg('reservation_degree') ?? 0;

        // Calculate overall performance
        $overallPerformance = 0;
        $count = 0;
        if ($avgMemorization > 0) {
            $overallPerformance += $avgMemorization;
            $count++;
        }
        if ($avgReservation > 0) {
            $overallPerformance += $avgReservation;
            $count++;
        }
        $averageOverall = $count > 0 ? round($overallPerformance / $count, 1) : 0;

        $totalEvaluated = $sessionReports->whereNotNull('new_memorization_degree')->count();

        return PerformanceDTO::fromQuranData([
            'average_overall_performance' => $averageOverall,
            'average_memorization_degree' => round($avgMemorization, 1),
            'average_reservation_degree' => round($avgReservation, 1),
            'sessions_evaluated' => $totalEvaluated,
        ]);
    }

    /**
     * Calculate Quran progress for individual circle
     */
    protected function calculateQuranProgress(
        QuranIndividualCircle $circle,
        Collection $completedSessions,
        Collection $sessionReports
    ): ProgressDTO {
        // Pages memorized from circle model
        $papersMemorized = $circle->papers_memorized_precise ?? $circle->papers_memorized ?? 0;
        $pagesMemorized = $papersMemorized > 0 ? $papersMemorized / 2 : 0;

        return ProgressDTO::forQuranProgress($pagesMemorized, 604);
    }

    /**
     * Generate trend data for charts
     */
    protected function generateQuranTrendData(Collection $completedSessions, Collection $sessionReports): TrendDataDTO
    {
        $recentSessions = $completedSessions->sortBy('scheduled_at');

        $labels = [];
        $attendanceData = [];
        $memorizationData = [];
        $reservationData = [];

        foreach ($recentSessions as $session) {
            $report = $sessionReports->where('session_id', $session->id)->first();

            // Format date label
            $dateLabel = $session->scheduled_at ? $session->scheduled_at->format('m/d') : '';
            $labels[] = $dateLabel;

            // Calculate attendance points (attended=1, late=0.5, absent=0) * 10 to scale to 0-10
            $attendancePoints = 0;
            if ($report && $report->attendance_status === AttendanceStatus::ATTENDED->value) {
                $attendancePoints = $report->is_late ? 5 : 10;
            }
            $attendanceData[] = $attendancePoints;

            // Memorization grade (0-10 scale)
            $memorizationData[] = $report && $report->new_memorization_degree > 0 ? $report->new_memorization_degree : null;

            // Reservation grade (0-10 scale)
            $reservationData[] = $report && $report->reservation_degree > 0 ? $report->reservation_degree : null;
        }

        return new TrendDataDTO(
            labels: $labels,
            attendance: $attendanceData,
            memorization: $memorizationData,
            reservation: $reservationData
        );
    }

    /**
     * Generate stats cards for individual circle
     *
     * @return array Array of StatDTO
     */
    protected function generateIndividualStatsCards(
        QuranIndividualCircle $circle,
        AttendanceDTO $attendance,
        PerformanceDTO $performance,
        ProgressDTO $progress
    ): array {
        return [
            new StatDTO(
                label: 'نسبة حضوري',
                value: number_format($attendance->attendanceRate, 0).'%',
                color: $attendance->getColorClass(),
                icon: 'ri-user-star-line'
            ),
            new StatDTO(
                label: 'الصفحات المحفوظة',
                value: number_format($progress->currentValue, 1),
                color: 'purple',
                icon: 'ri-book-open-line'
            ),
            new StatDTO(
                label: 'تقييمي العام',
                value: number_format($performance->averageOverall, 1).'/10',
                color: $performance->getColorClass(),
                icon: 'ri-star-line'
            ),
        ];
    }

    /**
     * Format current position in Quran
     */
    protected function formatCurrentPosition(QuranIndividualCircle $circle): string
    {
        if (! $circle->current_page || ! $circle->current_face) {
            return 'لم يتم تحديد الموضع بعد';
        }

        $faceName = $circle->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني';

        return "الصفحة {$circle->current_page} - {$faceName}";
    }
}
