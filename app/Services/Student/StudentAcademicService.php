<?php

namespace App\Services\Student;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing student's academic subscriptions and progress.
 *
 * Handles:
 * - Academic subscription queries
 * - Academic session listing
 * - Academic progress tracking
 */
class StudentAcademicService
{
    /**
     * Get active academic subscriptions for a student.
     *
     * @param User $user
     * @return Collection
     */
    public function getActiveSubscriptions(User $user): Collection
    {
        $academy = $user->academy;

        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->with(['academicTeacher', 'academicPackage'])
            ->get();
    }

    /**
     * Get all academic subscriptions for a student with details.
     *
     * @param User $user
     * @return Collection
     */
    public function getAllSubscriptions(User $user): Collection
    {
        $academy = $user->academy;

        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher.user', 'subject', 'gradeLevel', 'academicPackage'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get subscription with recent sessions.
     *
     * @param User $user
     * @param int $limit Number of recent sessions to fetch
     * @return Collection
     */
    public function getSubscriptionsWithRecentSessions(User $user, int $limit = 5): Collection
    {
        $subscriptions = $this->getActiveSubscriptions($user);

        // Load recent sessions for each subscription
        foreach ($subscriptions as $subscription) {
            $subscription->recentSessions = AcademicSession::where('academic_subscription_id', $subscription->id)
                ->orderBy('scheduled_at', 'desc')
                ->limit($limit)
                ->get();
        }

        return $subscriptions;
    }

    /**
     * Get academic session details for a student.
     *
     * @param User $user
     * @param string $sessionId
     * @return AcademicSession|null
     */
    public function getSessionDetails(User $user, string $sessionId): ?AcademicSession
    {
        $academy = $user->academy;

        return AcademicSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->with([
                'academicTeacher.user',
                'academicSubscription.academicPackage',
                'sessionReports',
                'academy',
            ])
            ->first();
    }

    /**
     * Get academic subscription details with sessions.
     *
     * @param User $user
     * @param string $subscriptionId
     * @return array|null
     */
    public function getSubscriptionDetails(User $user, string $subscriptionId): ?array
    {
        $academy = $user->academy;

        $subscription = AcademicSubscription::where('id', $subscriptionId)
            ->where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->with([
                'academicTeacher.user',
                'subject',
                'gradeLevel',
                'academicPackage',
                'sessions' => function ($query) {
                    $query->orderBy('scheduled_at');
                },
            ])
            ->first();

        if (!$subscription) {
            return null;
        }

        // Get upcoming and past sessions
        $upcomingSessions = AcademicSession::where('academic_subscription_id', $subscription->id)
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value, SessionStatus::ONGOING->value])
            ->orderBy('scheduled_at')
            ->with(['student', 'academicTeacher'])
            ->get();

        $pastSessions = AcademicSession::where('academic_subscription_id', $subscription->id)
            ->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::CANCELLED->value])
            ->orderByDesc('scheduled_at')
            ->with(['student', 'academicTeacher'])
            ->get();

        // Calculate progress summary
        $progressSummary = $this->calculateProgressSummary($subscription);

        return [
            'subscription' => $subscription,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'progressSummary' => $progressSummary,
        ];
    }

    /**
     * Calculate progress summary for a subscription.
     *
     * @param AcademicSubscription $subscription
     * @return array
     */
    public function calculateProgressSummary(AcademicSubscription $subscription): array
    {
        $allSessions = $subscription->sessions()->get();
        $totalSessions = $allSessions->count();
        $completedSessions = $allSessions->where('status', SessionStatus::COMPLETED)->count();
        $missedSessions = $allSessions->where('status', SessionStatus::ABSENT)->count();
        $attendanceRate = $subscription->attendance_rate;

        // Get homework/assignment data from session reports
        $sessionReports = AcademicSessionReport::whereIn('session_id', $allSessions->pluck('id'))
            ->where('student_id', $subscription->student_id)
            ->get();

        $totalAssignments = $sessionReports->count();
        $completedAssignments = $sessionReports->whereNotNull('homework_degree')->count();
        $homeworkCompletionRate = $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100) : 0;
        $overallGrade = $sessionReports->whereNotNull('homework_degree')->avg('homework_degree');
        $overallGrade = $overallGrade ? round($overallGrade * 10) : null; // Convert 0-10 to 0-100

        // Get last and next session dates
        $lastSession = $allSessions->where('status', SessionStatus::COMPLETED)->sortByDesc('scheduled_at')->first();
        $nextSession = $allSessions->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::ONGOING])->sortBy('scheduled_at')->first();

        // Calculate consecutive missed sessions
        $consecutiveMissed = 0;
        $sortedSessions = $allSessions->sortByDesc('scheduled_at');
        foreach ($sortedSessions as $session) {
            if ($session->status === SessionStatus::ABSENT) {
                $consecutiveMissed++;
            } else {
                break;
            }
        }

        return [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'sessions_completed' => $completedSessions,
            'sessions_planned' => $totalSessions,
            'sessions_missed' => $missedSessions,
            'progress_percentage' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
            'attendance_rate' => $attendanceRate,
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'homework_completion_rate' => $homeworkCompletionRate,
            'overall_grade' => $overallGrade,
            'needs_support' => $attendanceRate < 60 || ($overallGrade !== null && $overallGrade < 60),
            'progress_status' => $attendanceRate >= 80 ? 'ممتاز' : ($attendanceRate >= 60 ? 'جيد' : 'يحتاج تحسين'),
            'engagement_level' => $attendanceRate >= 80 ? 'عالي' : ($attendanceRate >= 60 ? 'متوسط' : 'منخفض'),
            'last_session' => $lastSession?->scheduled_at,
            'next_session' => $nextSession?->scheduled_at,
            'consecutive_missed' => $consecutiveMissed,
        ];
    }

    /**
     * Get student's academic subscriptions for progress display.
     *
     * @param User $user
     * @return Collection
     */
    public function getAcademicProgress(User $user): Collection
    {
        $academy = $user->academy;

        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['academicTeacher', 'subject', 'sessions'])
            ->get();
    }

    /**
     * Get student's subscriptions with specific teacher.
     *
     * @param User $user
     * @return Collection
     */
    public function getSubscriptionsByTeacher(User $user): Collection
    {
        $academy = $user->academy;

        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->with(['academicTeacher'])
            ->get();
    }
}
