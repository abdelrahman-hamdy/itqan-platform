<?php

namespace App\Services\Reports;

use App\DTOs\Reports\{AttendanceDTO, PerformanceDTO, ProgressDTO, StatDTO, TrendDataDTO};
use App\Models\{QuranCircle, QuranIndividualCircle, User};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * @param QuranIndividualCircle $circle
     * @param array|null $dateRange Optional date range filter ['start' => Carbon, 'end' => Carbon]
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
        $completedSessions = $sessions->whereIn('status', ['completed', 'absent']);

        // Get student session reports
        $sessionReports = DB::table('student_session_reports')
            ->whereIn('session_id', $sessions->pluck('id'))
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

            // Raw data for backward compatibility
            'sessions' => $sessions,
            'session_reports' => $sessionReports,

            // Overall circle info
            'overall' => [
                'started_at' => $circle->started_at,
                'total_sessions_planned' => $circle->total_sessions,
                'sessions_completed' => $circle->sessions_completed,
                'sessions_remaining' => $circle->sessions_remaining,
                'progress_percentage' => $circle->progress_percentage ?? 0,
            ],

            // Homework stats (backward compatibility)
            'homework' => $this->calculateHomeworkStatsForStudent($student, $sessions),
        ];
    }

    /**
     * Generate comprehensive report for group circle (all students)
     *
     * @param QuranCircle $circle
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
                'sessions_completed' => $circle->sessions_completed ?? $sessions->whereIn('status', ['completed'])->count(),
                'enrolled_students' => $students->count(),
                'max_students' => $circle->max_students,
            ],
        ];
    }

    /**
     * Generate report for specific student in group circle
     *
     * @param QuranCircle $circle
     * @param User $student
     * @param array|null $dateRange Optional date range filter
     * @return array Report data with DTOs
     */
    public function getStudentReportInGroupCircle(QuranCircle $circle, User $student, ?array $dateRange = null): array
    {
        // Get student's enrollment date from pivot table
        $enrollment = DB::table('quran_circle_students')
            ->where('circle_id', $circle->id)
            ->where('student_id', $student->id)
            ->first();

        $enrolledAt = $enrollment ? \Carbon\Carbon::parse($enrollment->enrolled_at) : null;

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

        $completedSessions = $allSessions->whereIn('status', ['completed', 'absent']);

        // Get student's session reports
        $sessionReports = DB::table('student_session_reports')
            ->whereIn('session_id', $allSessions->pluck('id'))
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
                'status' => $enrollment->status ?? 'active',
                'attendance_count' => $enrollment->attendance_count ?? 0,
                'missed_sessions' => $enrollment->missed_sessions ?? 0,
            ],

            // DTOs
            'attendance' => $attendanceDTO,
            'performance' => $performanceDTO,
            'trends' => $trendsDTO,

            // Raw data for backward compatibility
            'sessions' => $allSessions,
            'session_reports' => $sessionReports,

            // Progress data (kept as array for backward compatibility)
            'progress' => $this->calculateProgressStatsForStudent($student, $completedSessions, $sessionReports),
        ];
    }

    /**
     * Calculate Quran attendance with points-based system
     *
     * Points: attended=1, late=0.5, absent=0
     *
     * @param Collection $completedSessions
     * @param Collection $sessionReports
     * @return AttendanceDTO
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

        $attended = $sessionReports->where('attendance_status', 'attended')->count();
        $absent = $sessionReports->where('attendance_status', 'absent')->count();
        $late = $sessionReports->where('is_late', true)->count();

        $avgDuration = $sessionReports->where('attendance_status', 'attended')
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
     *
     * @param QuranIndividualCircle $circle
     * @param Collection $completedSessions
     * @param Collection $sessionReports
     * @return PerformanceDTO
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
     *
     * @param User $student
     * @param Collection $completedSessions
     * @param Collection $sessionReports
     * @return PerformanceDTO
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
     *
     * @param QuranIndividualCircle $circle
     * @param Collection $completedSessions
     * @param Collection $sessionReports
     * @return ProgressDTO
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
     *
     * @param Collection $completedSessions
     * @param Collection $sessionReports
     * @return TrendDataDTO
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
            if ($report && $report->attendance_status === 'attended') {
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
     * @param QuranIndividualCircle $circle
     * @param AttendanceDTO $attendance
     * @param PerformanceDTO $performance
     * @param ProgressDTO $progress
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
                value: number_format($attendance->attendanceRate, 0) . '%',
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
                label: 'الصفحات المُراجعة',
                value: $this->calculatePagesReviewed($circle),
                color: 'blue',
                icon: 'ri-refresh-line'
            ),
            new StatDTO(
                label: 'تقييمي العام',
                value: number_format($performance->averageOverall, 1) . '/10',
                color: $performance->getColorClass(),
                icon: 'ri-star-line'
            ),
        ];
    }

    /**
     * Calculate pages reviewed (estimation)
     *
     * @param QuranIndividualCircle $circle
     * @return int
     */
    protected function calculatePagesReviewed(QuranIndividualCircle $circle): int
    {
        // This is a simplified calculation - could be enhanced with actual data
        return 0; // Placeholder
    }

    /**
     * Calculate progress statistics for student in group circle
     * (Kept for backward compatibility)
     *
     * @param User $student
     * @param Collection $completedSessions
     * @param Collection $sessionReports
     * @return array
     */
    protected function calculateProgressStatsForStudent(User $student, Collection $completedSessions, Collection $sessionReports): array
    {
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
        $averageOverallPerformance = $count > 0 ? round($overallPerformance / $count, 1) : 0;

        // Estimate pages memorized
        $sessionsWithMemorization = $sessionReports->whereNotNull('new_memorization_degree')
            ->where('new_memorization_degree', '>', 0)
            ->count();

        $estimatedPagesPerSession = $avgMemorization > 0 ? ($avgMemorization / 10) : 0.5;
        $totalPagesMemorized = round($sessionsWithMemorization * $estimatedPagesPerSession, 1);

        $averagePagesPerSession = $sessionsWithMemorization > 0 ?
            round($totalPagesMemorized / $sessionsWithMemorization, 2) : 0;

        // Calculate pages reviewed
        $sessionsWithReview = $sessionReports->whereNotNull('reservation_degree')
            ->where('reservation_degree', '>', 0)
            ->count();
        $estimatedPagesReviewed = $sessionsWithReview * 3;

        // Calculate overall assessment
        $totalGrades = [];
        foreach ($sessionReports as $report) {
            if ($report->new_memorization_degree > 0) {
                $totalGrades[] = $report->new_memorization_degree;
            }
            if ($report->reservation_degree > 0) {
                $totalGrades[] = $report->reservation_degree;
            }
        }
        $overallAssessment = count($totalGrades) > 0 ? round(array_sum($totalGrades) / count($totalGrades), 1) : 0;

        return [
            'pages_memorized' => round($totalPagesMemorized, 1),
            'pages_reviewed' => $estimatedPagesReviewed,
            'average_memorization_degree' => round($avgMemorization, 1),
            'average_reservation_degree' => round($avgReservation, 1),
            'average_overall_performance' => $averageOverallPerformance,
            'overall_assessment' => $overallAssessment,
            'sessions_evaluated' => $sessionReports->whereNotNull('new_memorization_degree')->count(),
            'average_pages_per_session' => $averagePagesPerSession,
        ];
    }

    /**
     * Calculate homework statistics for specific student
     * (Kept for backward compatibility)
     *
     * @param User $student
     * @param Collection $sessions
     * @return array
     */
    protected function calculateHomeworkStatsForStudent(User $student, Collection $sessions): array
    {
        $totalAssigned = 0;
        $totalCompleted = 0;
        $totalScores = [];

        // Get student's session reports
        $sessionReports = DB::table('student_session_reports')
            ->whereIn('session_id', $sessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('session_id');

        foreach ($sessions as $session) {
            $homework = $session->sessionHomework;

            if ($homework && ($homework->has_new_memorization || $homework->has_review || $homework->has_comprehensive_review)) {
                $totalAssigned++;

                $report = $sessionReports->get($session->id);
                if ($report) {
                    $hasGrades = ($report->new_memorization_degree > 0) || ($report->reservation_degree > 0);

                    if ($hasGrades) {
                        $totalCompleted++;

                        $scores = [];
                        if ($homework->has_new_memorization && $report->new_memorization_degree > 0) {
                            $scores[] = $report->new_memorization_degree;
                        }
                        if (($homework->has_review || $homework->has_comprehensive_review) && $report->reservation_degree > 0) {
                            $scores[] = $report->reservation_degree;
                        }

                        if (count($scores) > 0) {
                            $totalScores[] = array_sum($scores) / count($scores);
                        }
                    }
                }
            }
        }

        return [
            'total_assigned' => $totalAssigned,
            'completed' => $totalCompleted,
            'in_progress' => 0,
            'not_started' => max(0, $totalAssigned - $totalCompleted),
            'completion_rate' => $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100, 1) : 0,
            'average_score' => count($totalScores) > 0 ? round(array_sum($totalScores) / count($totalScores), 1) : 0,
        ];
    }

    /**
     * Format current position in Quran
     *
     * @param QuranIndividualCircle $circle
     * @return string
     */
    protected function formatCurrentPosition(QuranIndividualCircle $circle): string
    {
        if (!$circle->current_page || !$circle->current_face) {
            return 'لم يتم تحديد الموضع بعد';
        }

        $faceName = $circle->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني';
        return "الصفحة {$circle->current_page} - {$faceName}";
    }
}
