<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use App\Services\Unified\UnifiedSessionFetchingService;
use App\Services\Unified\UnifiedStatisticsService;
use App\Services\Unified\UnifiedSubscriptionFetchingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Parent Data Service
 *
 * Centralized service for fetching all child data with authorization checks.
 * All methods validate parent-child relationship before returning data.
 *
 * Uses unified services for session, subscription, and statistics fetching.
 */
class ParentDataService
{
    public function __construct(
        private UnifiedSessionFetchingService $sessionService,
        private UnifiedSubscriptionFetchingService $subscriptionService,
        private UnifiedStatisticsService $statsService,
    ) {}

    /**
     * Get all linked children for parent
     */
    public function getChildren(ParentProfile $parent): Collection
    {
        return $parent->students()
            ->forAcademy($parent->academy_id)
            ->with(['user'])
            ->get();
    }

    /**
     * Get specific child data (validates parent owns child)
     */
    public function getChildData(ParentProfile $parent, int $childId): array
    {
        $child = $this->validateChildAccess($parent, $childId);

        return [
            'child' => $child,
            'user' => $child->user,
            'relationship_type' => $child->pivot->relationship_type ?? null,
            'subscriptions_count' => $this->getChildSubscriptionsCount($child),
            'certificates_count' => $this->getChildCertificatesCount($child),
            'upcoming_sessions_count' => $this->getChildUpcomingSessionsCount($child),
        ];
    }

    /**
     * Get subscriptions for child
     *
     * Uses UnifiedSubscriptionFetchingService for consistent data format.
     */
    public function getChildSubscriptions(ParentProfile $parent, int $childId): array
    {
        $child = $this->validateChildAccess($parent, $childId);

        // Use unified service - returns normalized subscriptions grouped by type
        return $this->subscriptionService->getGroupedByType(
            $child->user_id,
            $parent->academy_id
        );
    }

    /**
     * Get upcoming sessions for child
     *
     * Uses UnifiedSessionFetchingService for consistent data format.
     */
    public function getChildUpcomingSessions(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);

        // Use unified service - returns normalized sessions sorted by scheduled_at
        return $this->sessionService->getUpcoming(
            [$child->user_id],
            $parent->academy_id,
            30 // Next 30 days
        );
    }

    /**
     * Get payment history for child
     */
    public function getChildPayments(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        return Payment::where('user_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['subscription'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get certificates for child
     */
    public function getChildCertificates(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        return Certificate::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['certificateable', 'issuedBy'])
            ->orderBy('issued_at', 'desc')
            ->get();
    }

    /**
     * Get quiz results for child
     */
    public function getChildQuizResults(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);

        return QuizAttempt::where('student_id', $child->id)
            ->with(['quizAssignment.quiz'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get child progress report
     *
     * Uses UnifiedStatisticsService for comprehensive statistics.
     */
    public function getChildProgressReport(ParentProfile $parent, int $childId): array
    {
        $child = $this->validateChildAccess($parent, $childId);

        // Use unified statistics service - handles caching internally
        $stats = $this->statsService->getStudentStatistics(
            $child->user_id,
            $parent->academy_id
        );

        $sessionStats = $stats['sessions'] ?? [];
        $quranStats = $sessionStats['quran'] ?? [];
        $academicStats = $sessionStats['academic'] ?? [];
        $totals = $sessionStats['totals'] ?? [];

        return [
            'quran_sessions_total' => $quranStats['total'] ?? 0,
            'quran_sessions_completed' => $quranStats['completed'] ?? 0,
            'academic_sessions_total' => $academicStats['total'] ?? 0,
            'academic_sessions_completed' => $academicStats['completed'] ?? 0,
            'total_sessions' => $totals['total'] ?? 0,
            'completed_sessions' => $totals['completed'] ?? 0,
            'attendance_rate' => $stats['attendance']['overall_rate'] ?? 0,
            'certificates_count' => $this->getChildCertificatesCount($child),
        ];
    }

    /**
     * Validates parent-child relationship, throws 403 if invalid
     */
    private function validateChildAccess(ParentProfile $parent, int $childId): StudentProfile
    {
        $child = $parent->students()
            ->where('student_profiles.id', $childId)
            ->forAcademy($parent->academy_id)
            ->first();

        if (! $child) {
            abort(403, 'لا يمكنك الوصول إلى بيانات هذا الطالب');
        }

        return $child;
    }

    /**
     * Get subscriptions count for child
     *
     * Uses UnifiedSubscriptionFetchingService.
     */
    private function getChildSubscriptionsCount(StudentProfile $child): int
    {
        $academyId = $child->academy_id ?? AcademyContextService::getCurrentAcademy()?->id;

        if (! $academyId) {
            return 0;
        }

        return $this->subscriptionService->getActive(
            $child->user_id,
            $academyId
        )->count();
    }

    /**
     * Get certificates count for child
     */
    private function getChildCertificatesCount(StudentProfile $child): int
    {
        return Certificate::where('student_id', $child->user_id)->count();
    }

    /**
     * Get upcoming sessions count for child
     *
     * Uses UnifiedSessionFetchingService.
     */
    private function getChildUpcomingSessionsCount(StudentProfile $child): int
    {
        $academyId = $child->academy_id ?? AcademyContextService::getCurrentAcademy()?->id;

        if (! $academyId) {
            return 0;
        }

        return $this->sessionService->getUpcoming(
            [$child->user_id],
            $academyId,
            30 // Next 30 days
        )->count();
    }

    /**
     * Clear all caches for a specific child.
     */
    public function clearChildCache(int $parentId, int $childId): void
    {
        Cache::forget("parent:child_progress:{$parentId}:{$childId}");
    }
}
