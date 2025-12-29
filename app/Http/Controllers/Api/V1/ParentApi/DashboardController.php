<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\SessionFetchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

/**
 * Parent Dashboard API Controller
 *
 * Demonstrates usage of the standardized ApiResponseService via ApiResponses trait.
 * Provides parent dashboard with children data, sessions, and subscriptions.
 */
class DashboardController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected SessionFetchingService $sessionFetchingService
    ) {
    }

    /**
     * Get parent dashboard data.
     *
     * Demonstrates ApiResponseService usage:
     * - notFoundResponse() for missing parent profile
     * - successResponse() with structured data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Use explicit relationship query instead of property access
        // Property access was returning null due to relationship caching issues
        $parentProfile = $user->parentProfile()->first();

        // Example: Using notFoundResponse() from ApiResponses trait
        if (!$parentProfile) {
            return $this->notFoundResponse(__('Parent profile not found.'));
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with(['student.user', 'student.gradeLevel'])
            ->get();

        $childrenData = $children->map(function ($relationship) {
            $student = $relationship->student;
            $studentUser = $student->user;

            // Get today's sessions for this child
            $todaySessions = $this->sessionFetchingService->getTodaySessionsCount($studentUser?->id ?? $student->id);

            // Get active subscriptions
            $activeSubscriptions = $this->getChildActiveSubscriptions($studentUser?->id ?? $student->id);

            return [
                'id' => $student->id,
                'user_id' => $studentUser?->id,
                'name' => $student->full_name,
                'student_code' => $student->student_code,
                'avatar' => $student->avatar ? asset('storage/' . $student->avatar) : null,
                'grade_level' => $student->gradeLevel?->name,
                'relationship' => $relationship->relationship_type,
                'today_sessions_count' => $todaySessions,
                'active_subscriptions_count' => $activeSubscriptions,
            ];
        })->toArray();

        // Get all children's upcoming sessions
        $childIds = $children->pluck('student.user.id')->filter()->toArray();
        $studentIds = $children->pluck('student.id')->toArray();
        $allIds = array_unique(array_merge($childIds, $studentIds));

        $upcomingSessions = $this->sessionFetchingService->getAllChildrenUpcomingSessions($allIds);

        // Get summary stats
        $stats = [
            'total_children' => count($childrenData),
            'total_today_sessions' => collect($childrenData)->sum('today_sessions_count'),
            'total_active_subscriptions' => collect($childrenData)->sum('active_subscriptions_count'),
            'upcoming_sessions' => count($upcomingSessions),
        ];

        $dashboardData = [
            'parent' => [
                'id' => $parentProfile->id,
                'name' => $parentProfile->first_name . ' ' . $parentProfile->last_name,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ],
            'children' => $childrenData,
            'stats' => $stats,
            'upcoming_sessions' => array_slice($upcomingSessions, 0, 5),
        ];

        // Example: Using successResponse() from ApiResponses trait
        // Returns standardized format: {success: true, message: "...", data: {...}}
        return $this->successResponse(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }

    /**
     * Get active subscriptions count for a child.
     */
    protected function getChildActiveSubscriptions(int $userId): int
    {
        $count = 0;

        $count += QuranSubscription::where('student_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->count();

        $count += AcademicSubscription::where('student_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->count();

        $count += CourseSubscription::where('student_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->count();

        return $count;
    }
}
