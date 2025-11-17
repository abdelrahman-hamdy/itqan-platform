<?php

namespace App\Services;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranProgress;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Quran Progress Service
 *
 * Manages QuranProgress model integration with the rest of the system.
 * Creates and updates progress records based on session reports.
 * All measurements are in PAGES (not verses).
 *
 * CRITICAL: Never modifies attendance data - reads only from StudentSessionReport
 */
class QuranProgressService
{
    /**
     * Create or update QuranProgress record based on StudentSessionReport
     * Called after StudentSessionReport is updated by auto-attendance system
     *
     * @param StudentSessionReport $report
     * @return QuranProgress|null
     */
    public function createOrUpdateSessionProgress(StudentSessionReport $report): ?QuranProgress
    {
        // Only create progress for sessions where student attended
        if ($report->attendance_status !== 'present') {
            return null;
        }

        $session = $report->session;
        if (!$session) {
            return null;
        }

        // Determine circle (individual or group)
        $circle = $session->individual_circle_id
            ? $session->individualCircle
            : $session->circle;

        if (!$circle) {
            return null;
        }

        // Calculate pages memorized today (from degrees)
        $pagesMemorizedToday = $this->calculatePagesFromDegrees(
            $report->new_memorization_degree,
            $report->reservation_degree
        );

        // Get or create progress record for this session
        $progress = QuranProgress::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $report->student_id,
            ],
            [
                'academy_id' => $session->academy_id,
                'quran_teacher_id' => $session->teacher_id,
                'quran_subscription_id' => $session->quran_subscription_id,
                'circle_id' => $circle->id,
                'progress_date' => $session->scheduled_at ?? now(),
                'progress_type' => 'session',

                // Current position (from circle)
                'current_surah' => $circle->current_surah,
                'current_verse' => $circle->current_verse ?? 0,
                'current_page' => $circle->current_page,
                'current_face' => $circle->current_face,

                // Today's progress (pages only - NO VERSES)
                'total_pages_memorized' => $pagesMemorizedToday,

                // Performance metrics (from report)
                'recitation_quality' => $report->new_memorization_degree,
                'tajweed_accuracy' => $report->reservation_degree,

                // Calculate overall performance
                'overall_rating' => $this->calculateOverallRating($report),

                // Status
                'progress_status' => 'recorded',
                'assessment_date' => now(),
            ]
        );

        // Update cumulative totals for this student
        $this->updateCumulativeTotals($report->student_id);

        return $progress;
    }

    /**
     * Calculate cumulative lifetime progress for a student
     *
     * @param User $student
     * @return array
     */
    public function calculateLifetimeProgress(User $student): array
    {
        $allProgress = QuranProgress::where('student_id', $student->id)
            ->orderBy('progress_date', 'asc')
            ->get();

        return [
            'total_pages_memorized' => $allProgress->sum('total_pages_memorized'),
            'total_sessions' => $allProgress->count(),
            'average_recitation_quality' => $allProgress->avg('recitation_quality'),
            'average_tajweed_accuracy' => $allProgress->avg('tajweed_accuracy'),
            'consistency_score' => $this->calculateConsistencyScore($allProgress),
            'first_session_date' => $allProgress->first()?->progress_date,
            'last_session_date' => $allProgress->last()?->progress_date,
        ];
    }

    /**
     * Update circle progress fields after session completion
     * Updates both current subscription and lifetime totals
     *
     * @param QuranIndividualCircle|QuranCircle $circle
     * @return void
     */
    public function updateCircleProgress($circle): void
    {
        if ($circle instanceof QuranIndividualCircle) {
            $this->updateIndividualCircleProgress($circle);
        } elseif ($circle instanceof QuranCircle) {
            $this->updateGroupCircleProgress($circle);
        }
    }

    /**
     * Update individual circle progress
     *
     * @param QuranIndividualCircle $circle
     * @return void
     */
    protected function updateIndividualCircleProgress(QuranIndividualCircle $circle): void
    {
        // Get all progress records for this circle
        $progressRecords = QuranProgress::where('circle_id', $circle->id)
            ->where('student_id', $circle->student_id)
            ->get();

        // Calculate totals
        $totalPagesMemorized = $progressRecords->sum('total_pages_memorized');
        $totalSessions = $progressRecords->count();

        // Get lifetime totals (all circles for this student)
        $lifetimeProgress = $this->calculateLifetimeProgress($circle->student);

        // Update circle
        $circle->update([
            // Current subscription progress
            'papers_memorized' => (int) floor($totalPagesMemorized * 2), // 1 page = 2 faces
            'papers_memorized_precise' => $totalPagesMemorized * 2,
            'sessions_completed' => $totalSessions,
            'progress_percentage' => $circle->total_sessions > 0
                ? min(100, ($totalSessions / $circle->total_sessions) * 100)
                : 0,

            // Lifetime totals
            'lifetime_sessions_completed' => $lifetimeProgress['total_sessions'],
            'lifetime_pages_memorized' => $lifetimeProgress['total_pages_memorized'],

            // Latest session date
            'last_session_at' => $progressRecords->last()?->progress_date,
        ]);
    }

    /**
     * Update group circle progress (aggregate for all students)
     *
     * @param QuranCircle $circle
     * @return void
     */
    protected function updateGroupCircleProgress(QuranCircle $circle): void
    {
        // For group circles, we update per-student in pivot table
        // This is handled by the observer updating pivot counters
        // Here we just update circle-level stats

        $completedSessions = $circle->sessions()
            ->whereIn('status', ['completed', 'absent'])
            ->count();

        $circle->update([
            'sessions_completed' => $completedSessions,
        ]);
    }

    /**
     * Track goal progress for a student (weekly/monthly)
     *
     * @param User $student
     * @return array
     */
    public function trackGoalProgress(User $student): array
    {
        // Get latest progress record with goals
        $latestProgress = QuranProgress::where('student_id', $student->id)
            ->whereNotNull('weekly_goal')
            ->latest('progress_date')
            ->first();

        if (!$latestProgress) {
            return [
                'has_goals' => false,
                'weekly_goal' => null,
                'monthly_goal' => null,
                'weekly_progress' => 0,
                'monthly_progress' => 0,
            ];
        }

        // Calculate progress this week
        $weeklyPages = QuranProgress::where('student_id', $student->id)
            ->where('progress_date', '>=', now()->startOfWeek())
            ->sum('total_pages_memorized');

        // Calculate progress this month
        $monthlyPages = QuranProgress::where('student_id', $student->id)
            ->where('progress_date', '>=', now()->startOfMonth())
            ->sum('total_pages_memorized');

        return [
            'has_goals' => true,
            'weekly_goal' => $latestProgress->weekly_goal,
            'monthly_goal' => $latestProgress->monthly_goal,
            'weekly_progress' => $weeklyPages,
            'monthly_progress' => $monthlyPages,
            'weekly_percentage' => $latestProgress->weekly_goal > 0
                ? min(100, ($weeklyPages / $latestProgress->weekly_goal) * 100)
                : 0,
            'monthly_percentage' => $latestProgress->monthly_goal > 0
                ? min(100, ($monthlyPages / $latestProgress->monthly_goal) * 100)
                : 0,
        ];
    }

    /**
     * Calculate pages from performance degrees
     * Rough estimation: 10/10 degree ≈ 0.5 pages (1 face)
     *
     * @param float|null $memorizationDegree
     * @param float|null $reservationDegree
     * @return float
     */
    protected function calculatePagesFromDegrees(?float $memorizationDegree, ?float $reservationDegree): float
    {
        $total = 0;

        // New memorization: full weight
        if ($memorizationDegree) {
            $total += ($memorizationDegree / 10) * 0.5; // Max 0.5 pages
        }

        // Reservation (review): half weight
        if ($reservationDegree) {
            $total += ($reservationDegree / 10) * 0.25; // Max 0.25 pages
        }

        return round($total, 2);
    }

    /**
     * Calculate overall rating from report
     *
     * @param StudentSessionReport $report
     * @return int
     */
    protected function calculateOverallRating(StudentSessionReport $report): int
    {
        $scores = array_filter([
            $report->new_memorization_degree,
            $report->reservation_degree,
        ]);

        if (empty($scores)) {
            return 0;
        }

        return (int) round(array_sum($scores) / count($scores));
    }

    /**
     * Calculate consistency score based on session regularity
     *
     * @param Collection $progressRecords
     * @return float
     */
    protected function calculateConsistencyScore($progressRecords): float
    {
        if ($progressRecords->count() < 2) {
            return 100; // Perfect score for new students
        }

        // Calculate average gap between sessions (in days)
        $dates = $progressRecords->pluck('progress_date')->map(fn($date) => $date->timestamp)->sort()->values();

        $gaps = [];
        for ($i = 1; $i < $dates->count(); $i++) {
            $gapDays = ($dates[$i] - $dates[$i - 1]) / (60 * 60 * 24);
            $gaps[] = $gapDays;
        }

        $avgGap = array_sum($gaps) / count($gaps);

        // Score: 100 for weekly sessions, decrease for irregular
        // Expected gap: 7 days
        $score = max(0, 100 - (abs($avgGap - 7) * 5));

        return round($score, 1);
    }

    /**
     * Update cumulative totals for a student (all-time stats)
     *
     * @param int $studentId
     * @return void
     */
    protected function updateCumulativeTotals(int $studentId): void
    {
        $lifetimePages = QuranProgress::where('student_id', $studentId)
            ->sum('total_pages_memorized');

        $totalSurahs = QuranProgress::where('student_id', $studentId)
            ->distinct('current_surah')
            ->count('current_surah');

        // Update the latest progress record with cumulative data
        QuranProgress::where('student_id', $studentId)
            ->latest('progress_date')
            ->first()
            ?->update([
                'total_pages_memorized' => $lifetimePages,
                'total_surahs_completed' => $totalSurahs,
            ]);
    }

    /**
     * Get progress summary for reports
     *
     * @param User $student
     * @param QuranIndividualCircle|QuranCircle|null $circle
     * @return array
     */
    public function getProgressSummary(User $student, $circle = null): array
    {
        $query = QuranProgress::where('student_id', $student->id);

        if ($circle) {
            $query->where('circle_id', $circle->id);
        }

        $records = $query->orderBy('progress_date', 'desc')->get();

        return [
            'total_sessions' => $records->count(),
            'total_pages_memorized' => $records->sum('total_pages_memorized'),
            'average_recitation_quality' => round($records->avg('recitation_quality'), 1),
            'average_tajweed_accuracy' => round($records->avg('tajweed_accuracy'), 1),
            'current_page' => $records->first()?->current_page,
            'current_face' => $records->first()?->current_face,
            'last_session_date' => $records->first()?->progress_date,
            'consistency_score' => $this->calculateConsistencyScore($records),
        ];
    }
}
