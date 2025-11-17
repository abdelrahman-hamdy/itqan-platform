<?php

namespace App\Services;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuranProgressService
{
    /**
     * Calculate comprehensive progress statistics for a Quran circle
     * Works with both QuranCircle (group) and QuranIndividualCircle (individual)
     */
    public function calculateProgressStats(Model $circle): array
    {
        // Validate circle type
        if (!$circle instanceof QuranCircle && !$circle instanceof QuranIndividualCircle) {
            throw new \InvalidArgumentException('Circle must be an instance of QuranCircle or QuranIndividualCircle');
        }

        // Get sessions collection
        $sessions = $circle->sessions;
        $completedSessions = $sessions->where('status', 'completed');
        $scheduledSessions = $sessions->where('status', 'scheduled');

        // Calculate attendance statistics
        $attendanceStats = $this->calculateAttendanceStats($completedSessions);

        // Calculate performance metrics
        $performanceMetrics = $this->calculatePerformanceMetrics($completedSessions);

        // Calculate papers memorized
        $totalPapers = $circle->papers_memorized_precise ??
            ($circle->verses_memorized ? $this->convertVersesToPapers($circle->verses_memorized) : 0);

        // Calculate learning analytics
        $papersPerSession = $completedSessions->count() > 0 && $totalPapers > 0
            ? $totalPapers / $completedSessions->count()
            : 0;

        return [
            // Basic session counts
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $completedSessions->count(),
            'scheduled_sessions' => $scheduledSessions->count(),
            'remaining_sessions' => max(0, ($circle->total_sessions ?? 0) - $completedSessions->count()),
            'progress_percentage' => $circle->progress_percentage ?? 0,

            // Attendance metrics (merged from calculateAttendanceStats)
            ...$attendanceStats,

            // Performance metrics (merged from calculatePerformanceMetrics)
            ...$performanceMetrics,

            // Progress metrics
            'total_papers_memorized' => $totalPapers,

            // Learning analytics
            'papers_per_session' => round($papersPerSession, 2),
            'consistency_score' => $this->calculateConsistencyScore($circle),
        ];
    }

    /**
     * Calculate attendance statistics from completed sessions
     */
    protected function calculateAttendanceStats(Collection $completedSessions): array
    {
        $attendedSessions = $completedSessions->where('attendance_status', 'attended')->count();
        $lateSessions = $completedSessions->where('attendance_status', 'late')->count();
        $absentSessions = $completedSessions->where('attendance_status', 'absent')->count();
        $leftEarlySessions = $completedSessions->whereIn('attendance_status', ['leaved', 'left_early'])->count();

        // For sessions without explicit attendance_status, assume attended if completed
        $completedWithoutStatus = $completedSessions->whereNull('attendance_status')->count();
        $totalAttended = $attendedSessions + $lateSessions + $leftEarlySessions + $completedWithoutStatus;

        $attendanceRate = $completedSessions->count() > 0
            ? ($totalAttended / $completedSessions->count()) * 100
            : 0;

        return [
            'attendance_rate' => round($attendanceRate, 2),
            'attended_sessions' => $attendedSessions,
            'late_sessions' => $lateSessions,
            'absent_sessions' => $absentSessions,
            'left_early_sessions' => $leftEarlySessions,
            'total_attended' => $totalAttended,
        ];
    }

    /**
     * Calculate performance metrics from completed sessions
     */
    protected function calculatePerformanceMetrics(Collection $completedSessions): array
    {
        return [
            'avg_recitation_quality' => round($completedSessions->avg('recitation_quality') ?? 0, 2),
            'avg_tajweed_accuracy' => round($completedSessions->avg('tajweed_accuracy') ?? 0, 2),
            'avg_session_duration' => round($completedSessions->avg('actual_duration_minutes') ?? 0, 2),
        ];
    }

    /**
     * Convert verses to approximate paper count (وجه)
     * Based on standard Quran structure
     *
     * @param int $verses Number of verses
     * @return float Number of papers (وجه)
     */
    public function convertVersesToPapers(int $verses): float
    {
        // Average verses per paper (وجه) in standard Mushaf
        // This varies by Surah, but 17.5 is a reasonable average
        $averageVersesPerPaper = 17.5;

        return round($verses / $averageVersesPerPaper, 2);
    }

    /**
     * Convert papers to verses
     *
     * @param float $papers Number of papers (وجه)
     * @return int Number of verses
     */
    public function convertPapersToVerses(float $papers): int
    {
        $averageVersesPerPaper = 17.5;

        return (int) round($papers * $averageVersesPerPaper);
    }

    /**
     * Calculate consistency score based on attendance pattern
     * Returns a score from 0-10 where 10 is most consistent
     *
     * @param Model $circle QuranCircle or QuranIndividualCircle
     * @return float Consistency score (0-10)
     */
    public function calculateConsistencyScore(Model $circle): float
    {
        $sessions = $circle->sessions()->where('status', 'completed')->orderBy('scheduled_at')->get();

        if ($sessions->count() < 2) {
            return 0;
        }

        $attendancePattern = $sessions->map(function ($session) {
            // Score: attended = 1, late = 0.7, leaved/left_early = 0.5, absent = 0
            return match ($session->attendance_status) {
                'attended' => 1.0,
                'late' => 0.7,
                'leaved', 'left_early' => 0.5,
                'absent' => 0.0,
                default => 1.0 // Default to attended if status not set
            };
        });

        // Calculate consistency based on variance in attendance
        $mean = $attendancePattern->avg();
        $variance = $attendancePattern->map(fn ($score) => pow($score - $mean, 2))->avg();

        // Convert to consistency score (0-10, where 10 is most consistent)
        $consistencyScore = max(0, 10 - ($variance * 20));

        return round($consistencyScore, 1);
    }

    /**
     * Get students for a circle (handles both group and individual circles)
     *
     * @param Model $circle QuranCircle or QuranIndividualCircle
     * @return Collection Collection of User models
     */
    public function getCircleStudents(Model $circle): Collection
    {
        if ($circle instanceof QuranCircle) {
            // Group circle - many students
            return $circle->students()->get();
        } elseif ($circle instanceof QuranIndividualCircle) {
            // Individual circle - single student
            return collect([$circle->student])->filter();
        }

        return collect();
    }

    /**
     * Calculate student-specific progress within a circle
     *
     * @param Model $circle QuranCircle or QuranIndividualCircle
     * @param int $studentId Student user ID
     * @return array Student progress statistics
     */
    public function calculateStudentProgress(Model $circle, int $studentId): array
    {
        $sessions = $circle->sessions()
            ->where('status', 'completed')
            ->get();

        // Get student reports for these sessions
        $studentReports = \App\Models\StudentSessionReport::where('student_id', $studentId)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->get();

        $attendedReports = $studentReports->whereIn('attendance_status', ['attended', 'late', 'leaved']);

        return [
            'total_sessions' => $studentReports->count(),
            'attended_sessions' => $attendedReports->count(),
            'missed_sessions' => $studentReports->where('attendance_status', 'absent')->count(),
            'attendance_rate' => $studentReports->count() > 0
                ? ($attendedReports->count() / $studentReports->count()) * 100
                : 0,
            'avg_memorization_degree' => $studentReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0,
            'avg_reservation_degree' => $studentReports->whereNotNull('reservation_degree')->avg('reservation_degree') ?: 0,
            'avg_attendance_percentage' => $studentReports->avg('attendance_percentage') ?: 0,
        ];
    }
}
