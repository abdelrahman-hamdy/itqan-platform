<?php

namespace App\Services;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Quran Circle Report Service
 *
 * Aggregates comprehensive report data for Quran circles (individual and group)
 * including attendance, progress, homework, and performance metrics.
 *
 * IMPORTANT: All progress tracking is pages-only (NOT verses)
 */
class QuranCircleReportService
{
    protected QuranProgressService $progressService;

    public function __construct(QuranProgressService $progressService)
    {
        $this->progressService = $progressService;
    }
    /**
     * Generate comprehensive report for individual circle
     *
     * @param QuranIndividualCircle $circle
     * @return array
     */
    public function getIndividualCircleReport(QuranIndividualCircle $circle): array
    {
        $student = $circle->student;
        $sessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();
        $completedSessions = $sessions->whereIn('status', ['completed', 'absent']);

        // Get student session reports
        $sessionReports = DB::table('student_session_reports')
            ->whereIn('session_id', $sessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get();

        return [
            'circle' => $circle,
            'student' => $student,
            'subscription' => $circle->subscription,
            'teacher' => $circle->quranTeacher,

            // Attendance Analytics
            'attendance' => $this->calculateAttendanceStats($completedSessions, $sessionReports),

            // Progress Analytics
            'progress' => $this->calculateProgressStats($circle, $completedSessions, $sessionReports),

            // Homework Analytics
            'homework' => $this->calculateHomeworkStats($sessions),

            // Performance Trends (for charts)
            'trends' => $this->generateTrendData($completedSessions, $sessionReports),

            // Session History
            'sessions' => $sessions,
            'session_reports' => $sessionReports,

            // Overall Stats
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
     * @param QuranCircle $circle
     * @return array
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
            if ($report['attendance']['total_sessions'] > 0) {
                $aggregateStats['total_attendance_rate'] += $report['attendance']['attendance_rate'];
                $aggregateStats['students_with_reports']++;
            }

            if (isset($report['progress']['average_overall_performance']) && $report['progress']['average_overall_performance'] > 0) {
                $aggregateStats['total_average_performance'] += $report['progress']['average_overall_performance'];
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
     * Important: Calculate from student's enrollment date, not circle start
     *
     * @param QuranCircle $circle
     * @param User $student
     * @return array
     */
    public function getStudentReportInGroupCircle(QuranCircle $circle, User $student): array
    {
        // Get student's enrollment date from pivot table
        $enrollment = DB::table('quran_circle_students')
            ->where('circle_id', $circle->id)
            ->where('student_id', $student->id)
            ->first();

        $enrolledAt = $enrollment ? \Carbon\Carbon::parse($enrollment->enrolled_at) : null;

        // Get sessions since student enrollment
        $allSessions = $circle->sessions()
            ->when($enrolledAt, function ($query) use ($enrolledAt) {
                return $query->where('scheduled_at', '>=', $enrolledAt);
            })
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $completedSessions = $allSessions->whereIn('status', ['completed', 'absent']);

        // Get student's session reports
        $sessionReports = DB::table('student_session_reports')
            ->whereIn('session_id', $allSessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get();

        return [
            'student' => $student,
            'enrollment' => [
                'enrolled_at' => $enrolledAt,
                'status' => $enrollment->status ?? 'active',
                'attendance_count' => $enrollment->attendance_count ?? 0,
                'missed_sessions' => $enrollment->missed_sessions ?? 0,
            ],

            // Attendance Analytics (from enrollment date)
            'attendance' => $this->calculateAttendanceStats($completedSessions, $sessionReports),

            // Progress Analytics
            'progress' => $this->calculateProgressStatsForStudent($student, $completedSessions, $sessionReports),

            // Homework Analytics
            'homework' => $this->calculateHomeworkStatsForStudent($student, $allSessions),

            // Performance Trends
            'trends' => $this->generateTrendData($completedSessions, $sessionReports),

            // Session History
            'sessions' => $allSessions,
            'session_reports' => $sessionReports,
        ];
    }

    /**
     * Calculate attendance statistics
     */
    protected function calculateAttendanceStats(Collection $completedSessions, Collection $sessionReports): array
    {
        $totalSessions = $completedSessions->count();

        if ($totalSessions === 0) {
            return [
                'total_sessions' => 0,
                'attended' => 0,
                'absent' => 0,
                'late' => 0,
                'attendance_rate' => 0,
                'average_duration_minutes' => 0,
                'punctuality_rate' => 0,
            ];
        }

        $attended = $sessionReports->where('attendance_status', 'present')->count();
        $absent = $sessionReports->where('attendance_status', 'absent')->count();
        $late = $sessionReports->where('is_late', true)->count();

        $avgDuration = $sessionReports->where('attendance_status', 'present')
            ->avg('actual_attendance_minutes') ?? 0;

        return [
            'total_sessions' => $totalSessions,
            'attended' => $attended,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => $totalSessions > 0 ? round(($attended / $totalSessions) * 100, 1) : 0,
            'average_duration_minutes' => round($avgDuration, 0),
            'punctuality_rate' => $attended > 0 ? round((($attended - $late) / $attended) * 100, 1) : 100,
        ];
    }

    /**
     * Calculate progress statistics for individual circle
     * Uses QuranProgress model for cumulative tracking (pages-only)
     */
    protected function calculateProgressStats(QuranIndividualCircle $circle, Collection $completedSessions, Collection $sessionReports): array
    {
        $student = $circle->student;

        // Get cumulative progress from QuranProgress
        $quranProgressData = QuranProgress::where('student_id', $student->id)
            ->where('circle_id', $circle->id)
            ->orderBy('progress_date', 'desc')
            ->first();

        // Calculate performance averages from session reports
        $totalMemorization = $sessionReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?? 0;
        $totalReservation = $sessionReports->whereNotNull('reservation_degree')->avg('reservation_degree') ?? 0;

        // Calculate overall performance (average of both)
        $overallPerformance = 0;
        $count = 0;
        if ($totalMemorization > 0) {
            $overallPerformance += $totalMemorization;
            $count++;
        }
        if ($totalReservation > 0) {
            $overallPerformance += $totalReservation;
            $count++;
        }
        $averageOverallPerformance = $count > 0 ? round($overallPerformance / $count, 1) : 0;

        // Pages memorized (from QuranProgress or circle model)
        $pagesMemorized = $quranProgressData ? $quranProgressData->total_pages_memorized :
                         ($circle->papers_memorized_precise ? $circle->papers_memorized_precise / 2 : 0);

        $papersMemorized = $circle->papers_memorized_precise ?? $circle->papers_memorized ?? 0;

        // Lifetime statistics (if available)
        $lifetimePagesMemorized = $circle->lifetime_pages_memorized ?? null;
        $lifetimeSessionsCompleted = $circle->lifetime_sessions_completed ?? null;

        // Calculate average pages per session
        $sessionsWithMemorization = $sessionReports->whereNotNull('new_memorization_degree')
            ->where('new_memorization_degree', '>', 0)
            ->count();
        $averagePagesPerSession = $sessionsWithMemorization > 0 ?
            round($pagesMemorized / $sessionsWithMemorization, 2) : 0;

        return [
            'current_position' => $this->formatCurrentPosition($circle),
            'pages_memorized' => round($pagesMemorized, 1),
            'papers_memorized' => $papersMemorized,
            'progress_percentage' => $circle->progress_percentage ?? 0,
            'average_memorization_degree' => round($totalMemorization, 1),
            'average_reservation_degree' => round($totalReservation, 1),
            'average_overall_performance' => $averageOverallPerformance,
            'sessions_evaluated' => $sessionReports->whereNotNull('new_memorization_degree')->count(),
            'average_pages_per_session' => $averagePagesPerSession,

            // Lifetime stats (if available)
            'lifetime_pages_memorized' => $lifetimePagesMemorized,
            'lifetime_sessions_completed' => $lifetimeSessionsCompleted,
        ];
    }

    /**
     * Calculate progress statistics for student in group circle
     * Uses QuranProgress model for cumulative tracking (pages-only)
     */
    protected function calculateProgressStatsForStudent(User $student, Collection $completedSessions, Collection $sessionReports): array
    {
        // Calculate performance averages from session reports
        $totalMemorization = $sessionReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?? 0;
        $totalReservation = $sessionReports->whereNotNull('reservation_degree')->avg('reservation_degree') ?? 0;

        // Calculate overall performance
        $overallPerformance = 0;
        $count = 0;
        if ($totalMemorization > 0) {
            $overallPerformance += $totalMemorization;
            $count++;
        }
        if ($totalReservation > 0) {
            $overallPerformance += $totalReservation;
            $count++;
        }
        $averageOverallPerformance = $count > 0 ? round($overallPerformance / $count, 1) : 0;

        // Get pages memorized from QuranProgress (for this student in all circles)
        $totalPagesMemorized = QuranProgress::where('student_id', $student->id)
            ->sum('total_pages_memorized') ?? 0;

        // Calculate average pages per session
        $sessionsWithMemorization = $sessionReports->whereNotNull('new_memorization_degree')
            ->where('new_memorization_degree', '>', 0)
            ->count();
        $averagePagesPerSession = $sessionsWithMemorization > 0 ?
            round($totalPagesMemorized / $sessionsWithMemorization, 2) : 0;

        return [
            'pages_memorized' => round($totalPagesMemorized, 1),
            'average_memorization_degree' => round($totalMemorization, 1),
            'average_reservation_degree' => round($totalReservation, 1),
            'average_overall_performance' => $averageOverallPerformance,
            'sessions_evaluated' => $sessionReports->whereNotNull('new_memorization_degree')->count(),
            'average_pages_per_session' => $averagePagesPerSession,
        ];
    }

    /**
     * Calculate homework statistics
     */
    protected function calculateHomeworkStats(Collection $sessions): array
    {
        $homeworkData = [];
        $totalAssigned = 0;
        $totalCompleted = 0;
        $totalInProgress = 0;
        $totalScores = [];

        foreach ($sessions as $session) {
            $homework = $session->sessionHomework;
            if ($homework) {
                $assignments = $homework->assignments ?? collect();
                $totalAssigned += $assignments->count();

                foreach ($assignments as $assignment) {
                    if ($assignment->completion_status === 'completed') {
                        $totalCompleted++;
                        if ($assignment->overall_score) {
                            $totalScores[] = $assignment->overall_score;
                        }
                    } elseif ($assignment->completion_status === 'in_progress') {
                        $totalInProgress++;
                    }
                }
            }
        }

        return [
            'total_assigned' => $totalAssigned,
            'completed' => $totalCompleted,
            'in_progress' => $totalInProgress,
            'not_started' => $totalAssigned - $totalCompleted - $totalInProgress,
            'completion_rate' => $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100, 1) : 0,
            'average_score' => count($totalScores) > 0 ? round(array_sum($totalScores) / count($totalScores), 1) : 0,
        ];
    }

    /**
     * Calculate homework statistics for specific student
     */
    protected function calculateHomeworkStatsForStudent(User $student, Collection $sessions): array
    {
        $totalAssigned = 0;
        $totalCompleted = 0;
        $totalInProgress = 0;
        $totalScores = [];

        foreach ($sessions as $session) {
            $homework = $session->sessionHomework;
            if ($homework) {
                $assignment = $homework->assignments()->where('student_id', $student->id)->first();
                if ($assignment) {
                    $totalAssigned++;

                    if ($assignment->completion_status === 'completed') {
                        $totalCompleted++;
                        if ($assignment->overall_score) {
                            $totalScores[] = $assignment->overall_score;
                        }
                    } elseif ($assignment->completion_status === 'in_progress') {
                        $totalInProgress++;
                    }
                }
            }
        }

        return [
            'total_assigned' => $totalAssigned,
            'completed' => $totalCompleted,
            'in_progress' => $totalInProgress,
            'not_started' => $totalAssigned - $totalCompleted - $totalInProgress,
            'completion_rate' => $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100, 1) : 0,
            'average_score' => count($totalScores) > 0 ? round(array_sum($totalScores) / count($totalScores), 1) : 0,
        ];
    }

    /**
     * Generate trend data for charts (last 10 sessions)
     */
    protected function generateTrendData(Collection $completedSessions, Collection $sessionReports): array
    {
        // Get last 10 completed sessions (chronological order)
        $recentSessions = $completedSessions->sortBy('scheduled_at')->take(10);

        $attendanceTrend = [];
        $performanceTrend = [];

        foreach ($recentSessions as $session) {
            $report = $sessionReports->where('session_id', $session->id)->first();

            $attendanceTrend[] = [
                'date' => $session->scheduled_at ? $session->scheduled_at->format('Y-m-d') : null,
                'status' => $report ? $report->attendance_status : 'unknown',
                'duration' => $report ? $report->actual_attendance_minutes : 0,
            ];

            if ($report) {
                $performanceScore = null;
                if ($report->new_memorization_degree && $report->reservation_degree) {
                    $performanceScore = ($report->new_memorization_degree + $report->reservation_degree) / 2;
                } elseif ($report->new_memorization_degree) {
                    $performanceScore = $report->new_memorization_degree;
                } elseif ($report->reservation_degree) {
                    $performanceScore = $report->reservation_degree;
                }

                if ($performanceScore) {
                    $performanceTrend[] = [
                        'date' => $session->scheduled_at ? $session->scheduled_at->format('Y-m-d') : null,
                        'score' => round($performanceScore, 1),
                    ];
                }
            }
        }

        return [
            'attendance' => $attendanceTrend,
            'performance' => $performanceTrend,
        ];
    }

    /**
     * Format current position in Quran (page and face)
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
