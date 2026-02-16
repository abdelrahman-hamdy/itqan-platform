<?php

namespace App\Services\Student;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

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
     * Note: Despite the method name, this includes PENDING and CANCELLED (not expired)
     * subscriptions so students can see their paid periods until expiry.
     */
    public function getActiveSubscriptions(User $user): Collection
    {
        $academy = $user->academy;

        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where(function ($query) {
                // Include ACTIVE and PENDING subscriptions
                $query->whereIn('status', [
                    SessionSubscriptionStatus::ACTIVE->value,
                    SessionSubscriptionStatus::PENDING->value,
                ])
                // ALSO include CANCELLED subscriptions that haven't reached end date yet
                // (paid period should remain accessible until ends_at)
                ->orWhere(function ($q) {
                    $q->where('status', SessionSubscriptionStatus::CANCELLED->value)
                        ->where(function ($dateQuery) {
                            $dateQuery->where('ends_at', '>', now())
                                ->orWhereNull('ends_at'); // Include if no end date set yet
                        });
                });
            })
            ->with(['academicTeacher', 'academicPackage'])
            ->get();
    }

    /**
     * Get all academic subscriptions for a student with details.
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
     * @param  int  $limit  Number of recent sessions to fetch
     */
    public function getSubscriptionsWithRecentSessions(User $user, int $limit = 5): Collection
    {
        $subscriptions = $this->getActiveSubscriptions($user);

        if ($subscriptions->isEmpty()) {
            return $subscriptions;
        }

        // Fetch all recent sessions in a single query, grouped by subscription
        $allSessions = AcademicSession::whereIn('academic_subscription_id', $subscriptions->pluck('id'))
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->groupBy('academic_subscription_id');

        // Assign limited sessions to each subscription
        foreach ($subscriptions as $subscription) {
            $subscription->recentSessions = ($allSessions[$subscription->id] ?? collect())->take($limit);
        }

        return $subscriptions;
    }

    /**
     * Get academic session details for a student.
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

        if (! $subscription) {
            return null;
        }

        // Get upcoming and past sessions
        $upcomingSessions = AcademicSession::where('academic_subscription_id', $subscription->id)
            ->active()
            ->orderBy('scheduled_at')
            ->with(['student', 'academicTeacher'])
            ->get();

        $pastSessions = AcademicSession::where('academic_subscription_id', $subscription->id)
            ->finished()
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
        $nextSession = $allSessions->whereIn('status', SessionStatus::activeStatuses())->sortBy('scheduled_at')->first();

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
     */
    public function getSubscriptionsByTeacher(User $user): Collection
    {
        $academy = $user->academy;

        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where(function ($query) {
                // Include ACTIVE and PENDING subscriptions
                $query->whereIn('status', [
                    SessionSubscriptionStatus::ACTIVE->value,
                    SessionSubscriptionStatus::PENDING->value,
                ])
                // ALSO include CANCELLED subscriptions that haven't reached end date yet
                ->orWhere(function ($q) {
                    $q->where('status', SessionSubscriptionStatus::CANCELLED->value)
                        ->where(function ($dateQuery) {
                            $dateQuery->where('ends_at', '>', now())
                                ->orWhereNull('ends_at');
                        });
                });
            })
            ->with(['academicTeacher'])
            ->get();
    }
}
