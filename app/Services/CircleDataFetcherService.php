<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Circle Data Fetcher Service
 *
 * Handles data fetching and queries for Quran circle reports.
 * Retrieves sessions, session reports, attendance data, and enrollment information
 * for both individual and group circles.
 */
class CircleDataFetcherService
{
    /**
     * Fetch all data needed for individual circle report
     */
    public function fetchIndividualCircleData(QuranIndividualCircle $circle, ?array $dateRange = null): array
    {
        $student = $circle->student;

        // Build sessions query with optional date filter
        $sessionsQuery = $circle->sessions()->orderBy('scheduled_at', 'desc');

        if ($dateRange) {
            $sessionsQuery->whereBetween('scheduled_at', [$dateRange['start'], $dateRange['end']]);
        }

        $sessions = $sessionsQuery->get();
        $completedSessions = $sessions->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ABSENT->value]);

        // Get student session reports using Eloquent with automatic tenant scoping
        $sessionReports = StudentSessionReport::whereIn('session_id', $sessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get();

        return [
            'circle' => $circle,
            'student' => $student,
            'subscription' => $circle->subscription,
            'teacher' => $circle->quranTeacher,
            'sessions' => $sessions,
            'completed_sessions' => $completedSessions,
            'session_reports' => $sessionReports,
        ];
    }

    /**
     * Fetch all data needed for group circle report
     */
    public function fetchGroupCircleData(QuranCircle $circle): array
    {
        $students = $circle->students;
        $sessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();

        return [
            'circle' => $circle,
            'students' => $students,
            'sessions' => $sessions,
        ];
    }

    /**
     * Fetch student-specific data for group circle
     */
    public function fetchStudentDataInGroupCircle(QuranCircle $circle, User $student, ?array $dateRange = null): array
    {
        // Get student's enrollment date from pivot table using Eloquent relationship
        $studentInCircle = $circle->students()->where('student_id', $student->id)->first();
        $enrollment = $studentInCircle?->pivot;

        $enrolledAt = $enrollment && $enrollment->enrolled_at
            ? \Carbon\Carbon::parse($enrollment->enrolled_at)
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

        // Get student's session reports using Eloquent with automatic tenant scoping
        $sessionReports = StudentSessionReport::whereIn('session_id', $allSessions->pluck('id'))
            ->where('student_id', $student->id)
            ->get();

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'enrolled_at' => $enrolledAt,
            'all_sessions' => $allSessions,
            'completed_sessions' => $completedSessions,
            'session_reports' => $sessionReports,
        ];
    }

    /**
     * Calculate attendance statistics from session data
     */
    public function calculateAttendanceStats(Collection $completedSessions, Collection $sessionReports): array
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

        $attended = $sessionReports->where('attendance_status', AttendanceStatus::ATTENDED->value)->count();
        $absent = $sessionReports->where('attendance_status', AttendanceStatus::ABSENT->value)->count();
        $late = $sessionReports->where('is_late', true)->count();

        $avgDuration = $sessionReports->where('attendance_status', AttendanceStatus::ATTENDED->value)
            ->avg('actual_attendance_minutes') ?? 0;

        // Calculate attendance using points system
        // Attended = 1 point, Late = 0.5 points, Absent = 0 points
        $totalPoints = 0;
        foreach ($sessionReports as $report) {
            if ($report->attendance_status === AttendanceStatus::ATTENDED->value) {
                $totalPoints += $report->is_late ? 0.5 : 1.0;
            }
            // Absent = 0 points (no addition)
        }

        $attendanceRate = $totalSessions > 0 ? round(($totalPoints / $totalSessions) * 100, 1) : 0;

        return [
            'total_sessions' => $totalSessions,
            'attended' => $attended,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => $attendanceRate,
            'average_duration_minutes' => round($avgDuration, 0),
            'punctuality_rate' => $attended > 0 ? round((($attended - $late) / $attended) * 100, 1) : 100,
        ];
    }

    /**
     * Calculate progress statistics for individual circle
     */
    public function calculateProgressStats(QuranIndividualCircle $circle, Collection $completedSessions, Collection $sessionReports): array
    {
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

        // Pages memorized from circle model (papers_memorized_precise / 2 = pages)
        $papersMemorized = $circle->papers_memorized_precise ?? $circle->papers_memorized ?? 0;
        $pagesMemorized = $papersMemorized > 0 ? $papersMemorized / 2 : 0;

        // Calculate average pages per session
        $sessionsWithMemorization = $sessionReports->whereNotNull('new_memorization_degree')
            ->where('new_memorization_degree', '>', 0)
            ->count();
        $averagePagesPerSession = $sessionsWithMemorization > 0 ?
            round($pagesMemorized / $sessionsWithMemorization, 2) : 0;

        // Calculate pages reviewed (estimated from reservation/review degrees)
        $sessionsWithReview = $sessionReports->whereNotNull('reservation_degree')
            ->where('reservation_degree', '>', 0)
            ->count();

        // Estimate: each review session covers approximately 3 pages
        $estimatedPagesReviewed = $sessionsWithReview * 3;

        // Calculate overall assessment (average of memorization and review grades)
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
            'pages_memorized' => round($pagesMemorized, 1),
            'papers_memorized' => $papersMemorized,
            'pages_reviewed' => $estimatedPagesReviewed,
            'progress_percentage' => $circle->progress_percentage ?? 0,
            'average_memorization_degree' => round($totalMemorization, 1),
            'average_reservation_degree' => round($totalReservation, 1),
            'average_overall_performance' => $averageOverallPerformance,
            'overall_assessment' => $overallAssessment,
            'sessions_evaluated' => $sessionReports->whereNotNull('new_memorization_degree')->count(),
            'average_pages_per_session' => $averagePagesPerSession,
            'lifetime_pages_memorized' => $circle->lifetime_pages_memorized ?? null,
            'lifetime_sessions_completed' => $circle->lifetime_sessions_completed ?? null,
        ];
    }

    /**
     * Calculate progress statistics for student in group circle
     */
    public function calculateProgressStatsForStudent(Collection $completedSessions, Collection $sessionReports): array
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

        // Estimate pages memorized from session count and average performance
        $sessionsWithMemorization = $sessionReports->whereNotNull('new_memorization_degree')
            ->where('new_memorization_degree', '>', 0)
            ->count();

        // Estimate: each successful memorization session covers approximately 0.5-1 page
        $estimatedPagesPerSession = $totalMemorization > 0 ? ($totalMemorization / 10) : 0.5;
        $totalPagesMemorized = round($sessionsWithMemorization * $estimatedPagesPerSession, 1);

        // Calculate average pages per session
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
            'average_memorization_degree' => round($totalMemorization, 1),
            'average_reservation_degree' => round($totalReservation, 1),
            'average_overall_performance' => $averageOverallPerformance,
            'overall_assessment' => $overallAssessment,
            'sessions_evaluated' => $sessionReports->whereNotNull('new_memorization_degree')->count(),
            'average_pages_per_session' => $averagePagesPerSession,
        ];
    }

    /**
     * Generate trend data for charts (last 10 sessions)
     */
    public function generateTrendData(Collection $completedSessions, Collection $sessionReports): array
    {
        // Get all completed sessions in chronological order
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

        return [
            'labels' => $labels,
            'attendance' => $attendanceData,
            'memorization' => $memorizationData,
            'reservation' => $reservationData,
        ];
    }
}
