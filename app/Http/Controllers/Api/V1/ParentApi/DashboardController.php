<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

class DashboardController extends Controller
{
    use ApiResponses;

    /**
     * Get parent dashboard data.
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

        if (!$parentProfile) {
            return $this->error(
                __('Parent profile not found.'),
                404,
                'PARENT_PROFILE_NOT_FOUND'
            );
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with(['student.user', 'student.gradeLevel'])
            ->get();

        $childrenData = $children->map(function ($relationship) {
            $student = $relationship->student;
            $studentUser = $student->user;

            // Get today's sessions for this child
            $todaySessions = $this->getChildTodaySessions($studentUser?->id ?? $student->id);

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
                'today_sessions_count' => count($todaySessions),
                'active_subscriptions_count' => $activeSubscriptions,
            ];
        })->toArray();

        // Get all children's upcoming sessions
        $childIds = $children->pluck('student.user.id')->filter()->toArray();
        $studentIds = $children->pluck('student.id')->toArray();
        $allIds = array_unique(array_merge($childIds, $studentIds));

        $upcomingSessions = $this->getAllChildrenUpcomingSessions($allIds);

        // Get summary stats
        $stats = [
            'total_children' => count($childrenData),
            'total_today_sessions' => collect($childrenData)->sum('today_sessions_count'),
            'total_active_subscriptions' => collect($childrenData)->sum('active_subscriptions_count'),
            'upcoming_sessions' => count($upcomingSessions),
        ];

        return $this->success([
            'parent' => [
                'id' => $parentProfile->id,
                'name' => $parentProfile->first_name . ' ' . $parentProfile->last_name,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ],
            'children' => $childrenData,
            'stats' => $stats,
            'upcoming_sessions' => array_slice($upcomingSessions, 0, 5),
        ], __('Dashboard data retrieved successfully'));
    }

    /**
     * Get today's sessions for a child.
     */
    protected function getChildTodaySessions(int $userId): array
    {
        $today = Carbon::today();
        $sessions = [];

        // Quran sessions
        $quranCount = QuranSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value])
            ->count();

        // Academic sessions
        $academicCount = AcademicSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value])
            ->count();

        // Interactive sessions
        $interactiveCount = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value])
            ->count();

        return array_fill(0, $quranCount + $academicCount + $interactiveCount, true);
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

    /**
     * Get upcoming sessions for all children.
     */
    protected function getAllChildrenUpcomingSessions(array $userIds): array
    {
        $sessions = [];
        $now = now();
        $endDate = $now->copy()->addDays(7);

        foreach ($userIds as $userId) {
            // Quran sessions
            $quranSessions = QuranSession::where('student_id', $userId)
                ->where('scheduled_at', '>', $now)
                ->where('scheduled_at', '<=', $endDate)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['student.user', 'quranTeacher'])
                ->orderBy('scheduled_at')
                ->limit(3)
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = [
                    'id' => $session->id,
                    'type' => 'quran',
                    'title' => $session->title ?? 'جلسة قرآنية',
                    'child_name' => $session->student?->user?->name ?? $session->student?->full_name,
                    'teacher_name' => $session->quranTeacher?->name,
                    'scheduled_at' => $session->scheduled_at->toISOString(),
                ];
            }

            // Academic sessions
            $academicSessions = AcademicSession::where('student_id', $userId)
                ->where('scheduled_at', '>', $now)
                ->where('scheduled_at', '<=', $endDate)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['student.user', 'academicTeacher.user', 'academicSubscription'])
                ->orderBy('scheduled_at')
                ->limit(3)
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = [
                    'id' => $session->id,
                    'type' => 'academic',
                    'title' => $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                    'child_name' => $session->student?->user?->name ?? 'طالب',
                    'teacher_name' => $session->academicTeacher?->user?->name,
                    'scheduled_at' => $session->scheduled_at->toISOString(),
                ];
            }
        }

        // Sort by time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $sessions;
    }
}
