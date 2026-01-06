<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ParentStudentRelationship;
use App\Services\Unified\UnifiedSessionFetchingService;
use App\Services\Unified\UnifiedSubscriptionFetchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Parent Dashboard API Controller
 *
 * Provides parent dashboard with children data, sessions, and subscriptions.
 * Uses unified services for consistent data fetching across session and subscription types.
 */
class DashboardController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected UnifiedSessionFetchingService $sessionService,
        protected UnifiedSubscriptionFetchingService $subscriptionService
    ) {}

    /**
     * Get parent dashboard data.
     *
     * Uses unified services for session and subscription fetching.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;

        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->notFound(__('Parent profile not found.'));
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with(['student.user', 'student.gradeLevel'])
            ->get();

        // Collect all child user IDs
        $childIds = $children->pluck('student.user.id')->filter()->toArray();
        $studentIds = $children->pluck('student.id')->toArray();
        $allIds = array_unique(array_merge($childIds, $studentIds));

        // Batch fetch today's sessions for all children (prevents N+1)
        $allTodaySessions = $this->sessionService->getToday($allIds, $academyId);

        // Group today's sessions by student for quick lookup
        $sessionsByStudent = $allTodaySessions->groupBy(function ($session) {
            return $session['student_id'] ?? $session['user_id'] ?? null;
        });

        // Batch fetch subscription counts for all children (prevents N+1)
        $subscriptionCountsByStudent = [];
        foreach ($allIds as $studentId) {
            $counts = $this->subscriptionService->countByStatus($studentId, $academyId);
            $subscriptionCountsByStudent[$studentId] = $counts;
        }

        // Build children data with pre-fetched data (no N+1)
        $childrenData = $children->map(function ($relationship) use ($sessionsByStudent, $subscriptionCountsByStudent) {
            $student = $relationship->student;
            $studentUser = $student->user;
            $userId = $studentUser?->id ?? $student->id;

            // Lookup pre-fetched data
            $studentSessions = $sessionsByStudent->get($userId, collect())
                ->merge($sessionsByStudent->get($student->id, collect()));
            $subscriptionCounts = $subscriptionCountsByStudent[$userId]
                ?? $subscriptionCountsByStudent[$student->id]
                ?? ['active' => 0];

            return [
                'id' => $student->id,
                'user_id' => $studentUser?->id,
                'name' => $student->full_name,
                'student_code' => $student->student_code,
                'avatar' => $student->avatar ? asset('storage/'.$student->avatar) : null,
                'grade_level' => $student->gradeLevel?->name,
                'relationship' => $relationship->relationship_type,
                'today_sessions_count' => $studentSessions->count(),
                'active_subscriptions_count' => $subscriptionCounts['active'],
            ];
        })->toArray();

        // Get all children's upcoming sessions using unified service
        $upcomingSessions = $this->sessionService->getUpcoming($allIds, $academyId, 7);

        // Get summary stats
        $stats = [
            'total_children' => count($childrenData),
            'total_today_sessions' => collect($childrenData)->sum('today_sessions_count'),
            'total_active_subscriptions' => collect($childrenData)->sum('active_subscriptions_count'),
            'upcoming_sessions' => $upcomingSessions->count(),
        ];

        $dashboardData = [
            'parent' => [
                'id' => $parentProfile->id,
                'name' => $parentProfile->first_name.' '.$parentProfile->last_name,
                'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            ],
            'children' => $childrenData,
            'stats' => $stats,
            'upcoming_sessions' => $this->formatUpcomingSessionsForApi($upcomingSessions->take(5)),
        ];

        return $this->success(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }

    /**
     * Format upcoming sessions for API response.
     */
    protected function formatUpcomingSessionsForApi(\Illuminate\Support\Collection $sessions): array
    {
        return $sessions->map(function ($session) {
            return [
                'id' => $session['id'],
                'type' => $session['type'],
                'title' => $session['title'],
                'child_name' => $session['student_name'],
                'teacher_name' => $session['teacher_name'],
                'scheduled_at' => $session['scheduled_at']?->toISOString(),
            ];
        })->toArray();
    }
}
