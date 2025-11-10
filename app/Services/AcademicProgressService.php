<?php

namespace App\Services;

use App\Models\AcademicProgress;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AcademicProgressService
{
    /**
     * Get or create progress record for a subscription
     */
    public function getOrCreateProgress(AcademicSubscription $subscription): AcademicProgress
    {
        $progress = AcademicProgress::where('subscription_id', $subscription->id)
            ->where('student_id', $subscription->student_id)
            ->first();

        if (!$progress) {
            $progress = $this->createProgressRecord($subscription);
        }

        return $progress;
    }

    /**
     * Create a new progress record for a subscription
     */
    private function createProgressRecord(AcademicSubscription $subscription): AcademicProgress
    {
        return AcademicProgress::create([
            'academy_id' => $subscription->academy_id,
            'subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
            'teacher_id' => $subscription->teacher_id,
            'subject_id' => $subscription->subject_id,
            'start_date' => $subscription->start_date ?? now(),
            'total_sessions_planned' => $subscription->sessions_per_month ?? 0,
            'is_active' => true,
            'progress_status' => 'satisfactory',
            'created_by' => $subscription->student_id,
        ]);
    }

    /**
     * Update progress when a session is completed
     */
    public function updateFromCompletedSession(AcademicSession $session): void
    {
        try {
            $subscription = $session->subscription;
            if (!$subscription) {
                Log::warning('Academic session has no subscription', ['session_id' => $session->id]);
                return;
            }

            $progress = $this->getOrCreateProgress($subscription);

            // Record completed session
            $progress->recordCompletedSession($session->scheduled_at);

            // Recalculate metrics
            $this->recalculateMetrics($progress);

            Log::info('Progress updated from completed session', [
                'progress_id' => $progress->id,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update progress from completed session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update progress when attendance is marked
     */
    public function updateFromAttendance(AcademicSession $session, string $attendanceStatus): void
    {
        try {
            $subscription = $session->subscription;
            if (!$subscription) {
                return;
            }

            $progress = $this->getOrCreateProgress($subscription);

            // Update based on attendance status
            if (in_array($attendanceStatus, ['present', 'late'])) {
                $progress->recordCompletedSession($session->scheduled_at);
            } elseif ($attendanceStatus === 'absent') {
                $progress->recordMissedSession($session->scheduled_at);
            }

            // Recalculate metrics
            $this->recalculateMetrics($progress);

            Log::info('Progress updated from attendance', [
                'progress_id' => $progress->id,
                'session_id' => $session->id,
                'status' => $attendanceStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update progress from attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recalculate all metrics for a progress record
     */
    public function recalculateMetrics(AcademicProgress $progress): void
    {
        $subscription = $progress->subscription;
        if (!$subscription) {
            return;
        }

        // Get all sessions for this subscription
        $sessions = AcademicSession::where('academic_subscription_id', $subscription->id)->get();

        // Count sessions by status
        $scheduledCount = $sessions->where('is_scheduled', true)->count();
        $completedCount = $sessions->where('status', 'completed')->count();
        $cancelledCount = $sessions->where('status', 'cancelled')->count();

        // Calculate attendance from attendance records
        $attendanceRecords = \App\Models\AcademicSessionAttendance::whereIn(
            'academic_session_id',
            $sessions->pluck('id')
        )->get();

        $attendedCount = $attendanceRecords->whereIn('status', ['present', 'late'])->count();
        $absentCount = $attendanceRecords->where('status', 'absent')->count();

        // Update session counts
        $progress->update([
            'total_sessions_planned' => $scheduledCount,
            'total_sessions_completed' => $attendedCount,
            'total_sessions_missed' => $absentCount,
            'total_sessions_cancelled' => $cancelledCount,
            'last_session_date' => $sessions->where('status', 'completed')
                ->sortByDesc('scheduled_at')
                ->first()
                ?->scheduled_at,
            'next_session_date' => $sessions->where('status', 'scheduled')
                ->where('scheduled_at', '>', now())
                ->sortBy('scheduled_at')
                ->first()
                ?->scheduled_at,
        ]);

        // Calculate and update attendance rate
        $totalSessionsAttended = $attendedCount + $absentCount;
        if ($totalSessionsAttended > 0) {
            $attendanceRate = ($attendedCount / $totalSessionsAttended) * 100;
            $progress->update(['attendance_rate' => $attendanceRate]);
        }

        // Update risk flag
        $this->updateRiskFlag($progress);
    }

    /**
     * Update risk flag based on progress metrics
     */
    private function updateRiskFlag(AcademicProgress $progress): void
    {
        $needsSupport = false;

        // Check for low attendance
        if ($progress->attendance_rate < 50 && $progress->total_sessions_completed >= 3) {
            $needsSupport = true;
        }

        // Check for consecutive missed sessions
        if ($progress->consecutive_missed_sessions >= 3) {
            $needsSupport = true;
        }

        // Check for low grades
        if ($progress->overall_grade && $progress->overall_grade < 60) {
            $needsSupport = true;
        }

        // Check for low homework completion
        if ($progress->homework_completion_rate < 40 && $progress->total_assignments_given >= 3) {
            $needsSupport = true;
        }

        $progress->update(['needs_additional_support' => $needsSupport]);
    }

    /**
     * Update progress from homework assignment
     */
    public function recordHomeworkAssignment(AcademicSubscription $subscription, ?Carbon $dueDate = null): void
    {
        try {
            $progress = $this->getOrCreateProgress($subscription);
            $progress->assignHomework($dueDate);

            Log::info('Homework assignment recorded in progress', [
                'progress_id' => $progress->id,
                'subscription_id' => $subscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record homework assignment', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update progress from homework submission
     */
    public function recordHomeworkSubmission(AcademicSubscription $subscription, ?Carbon $submissionDate = null): void
    {
        try {
            $progress = $this->getOrCreateProgress($subscription);
            $progress->submitHomework($submissionDate);

            // Recalculate homework completion rate
            if ($progress->total_assignments_given > 0) {
                $completionRate = ($progress->total_assignments_completed / $progress->total_assignments_given) * 100;
                $progress->update(['homework_completion_rate' => $completionRate]);
            }

            Log::info('Homework submission recorded in progress', [
                'progress_id' => $progress->id,
                'subscription_id' => $subscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record homework submission', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update progress from quiz score
     */
    public function recordQuizScore(AcademicSubscription $subscription, float $score): void
    {
        try {
            $progress = $this->getOrCreateProgress($subscription);
            $progress->recordQuiz($score);

            Log::info('Quiz score recorded in progress', [
                'progress_id' => $progress->id,
                'subscription_id' => $subscription->id,
                'score' => $score,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record quiz score', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate monthly report for a progress record
     */
    public function generateMonthlyReport(AcademicProgress $progress): array
    {
        return $progress->generateMonthlyReport();
    }

    /**
     * Get progress summary for display
     */
    public function getProgressSummary(AcademicSubscription $subscription): array
    {
        $progress = $this->getOrCreateProgress($subscription);

        return [
            'attendance_rate' => round($progress->attendance_rate, 1),
            'sessions_completed' => $progress->total_sessions_completed,
            'sessions_planned' => $progress->total_sessions_planned,
            'sessions_missed' => $progress->total_sessions_missed,
            'homework_completion_rate' => round($progress->homework_completion_rate, 1),
            'total_assignments' => $progress->total_assignments_given,
            'completed_assignments' => $progress->total_assignments_completed,
            'overall_grade' => $progress->overall_grade ? round($progress->overall_grade, 1) : null,
            'progress_status' => $progress->progress_status_in_arabic,
            'needs_support' => $progress->needs_additional_support,
            'last_session' => $progress->last_session_date?->format('Y-m-d'),
            'next_session' => $progress->next_session_date?->format('Y-m-d'),
            'consecutive_missed' => $progress->consecutive_missed_sessions,
            'engagement_level' => $progress->engagement_level_in_arabic ?? 'غير محدد',
        ];
    }

    /**
     * Bulk recalculate metrics for all active progress records
     */
    public function recalculateAllMetrics(): void
    {
        $progressRecords = AcademicProgress::where('is_active', true)->get();

        foreach ($progressRecords as $progress) {
            $this->recalculateMetrics($progress);
        }

        Log::info('Bulk recalculation completed', [
            'total_records' => $progressRecords->count(),
        ]);
    }
}
