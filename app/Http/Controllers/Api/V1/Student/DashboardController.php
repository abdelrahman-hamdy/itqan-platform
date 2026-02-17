<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Models\AcademicHomework;
use Illuminate\Support\Collection;
use App\Models\QuranSubscription;
use App\Models\AcademicSubscription;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Services\Unified\UnifiedSessionFetchingService;
use App\Services\Unified\UnifiedSubscriptionFetchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student Dashboard API Controller
 *
 * Provides dashboard data including sessions, subscriptions, and quick stats.
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
     * Get student dashboard data.
     *
     * Uses unified services for session and subscription fetching.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;
        $studentProfile = $user->studentProfile()->first();

        if (! $studentProfile) {
            return $this->notFound(__('Student profile not found.'));
        }

        // Use unified session service for consistent data format
        $todaySessions = $this->sessionService->getToday([$user->id], $academyId);
        $upcomingSessions = $this->sessionService->getUpcoming([$user->id], $academyId, 7);

        // Use unified subscription service
        $subscriptionCounts = $this->subscriptionService->countByStatus($user->id, $academyId);

        // Get actual active subscriptions for dashboard display
        $activeSubscriptions = $this->getActiveSubscriptions($user->id, $academyId);

        // Get recent homework/quizzes count
        $pendingHomework = $this->getPendingHomeworkCount($user->id);
        $pendingQuizzes = $this->getPendingQuizzesCount($user->id);

        // Get unread notifications count
        $unreadNotifications = $user->unreadNotifications()->count();

        // Get student's enrolled circles
        $individualCircles = $this->getIndividualCircles($user->id, $academyId);
        $groupCircles = $this->getGroupCircles($user->id, $academyId);

        // Get quick stats
        $stats = [
            'today_sessions' => $todaySessions->count(),
            'upcoming_sessions' => $upcomingSessions->count(),
            'active_subscriptions' => $subscriptionCounts['active'],
            'pending_homework' => $pendingHomework,
            'pending_quizzes' => $pendingQuizzes,
            'unread_notifications' => $unreadNotifications,
        ];

        $dashboardData = [
            'student' => [
                'id' => $studentProfile->id,
                'name' => $studentProfile->full_name,
                'student_code' => $studentProfile->student_code,
                'avatar' => $studentProfile->avatar ? asset('storage/'.$studentProfile->avatar) : null,
                'grade_level' => $studentProfile->gradeLevel?->name,
            ],
            'stats' => $stats,
            'today_sessions' => $this->formatUnifiedSessionsForApi($todaySessions),
            'upcoming_sessions' => $this->formatUnifiedSessionsForApi($upcomingSessions),
            'active_subscriptions' => $activeSubscriptions,
            'individual_circles' => $individualCircles,
            'group_circles' => $groupCircles,
        ];

        return $this->success(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }

    /**
     * Get pending homework count.
     * Simplified: counts sessions with homework that don't have submissions.
     */
    protected function getPendingHomeworkCount(int $userId): int
    {
        // Get sessions with homework assigned
        $sessionsWithHomework = AcademicSession::where('student_id', $userId)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->pluck('id');

        if ($sessionsWithHomework->isEmpty()) {
            return 0;
        }

        // Count sessions that have submissions
        $sessionsWithSubmissions = AcademicHomework::whereIn('academic_session_id', $sessionsWithHomework)
            ->whereHas('submissions', function ($q) use ($userId) {
                $q->where('academic_homework_submissions.student_id', $userId);
            })
            ->pluck('academic_session_id');

        // Return count of sessions without submissions
        return $sessionsWithHomework->diff($sessionsWithSubmissions)->count();
    }

    /**
     * Get pending quizzes count.
     * Note: Quizzes use polymorphic assignments to educational units (circles, courses, etc.)
     * A proper implementation would require checking all units the student is enrolled in.
     * For now, returning 0 as a placeholder.
     */
    protected function getPendingQuizzesCount(int $userId): int
    {
        // QuizAssignments don't have user_id - they use polymorphic assignable_type/id
        // This would require the same logic as QuizController::getStudentAssignableIds()
        // For dashboard purposes, we return 0 as students can check quizzes directly
        return 0;
    }

    /**
     * Format unified sessions for API response.
     *
     * The unified service already provides normalized data, we just need
     * to transform it to the API response format.
     */
    protected function formatUnifiedSessionsForApi(Collection $sessions): array
    {
        return $sessions->map(function ($session) {
            return [
                'id' => $session['id'],
                'type' => $session['type'],
                'title' => $session['title'],
                'status' => $session['status'],
                'duration_minutes' => $session['duration_minutes'],
                'can_join' => $session['can_join'],
                'scheduled_at' => $session['scheduled_at']?->toISOString(),
                'teacher' => $session['teacher_name'] ? [
                    'name' => $session['teacher_name'],
                    'avatar' => $session['teacher_avatar'],
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Get student's individual Quran circles.
     */
    protected function getIndividualCircles(int $userId, int $academyId): array
    {
        $circles = QuranIndividualCircle::where('student_id', $userId)
            ->where('academy_id', $academyId)
            ->with(['quranTeacher']) // quranTeacher is already the User
            ->get();

        return $circles->map(function ($circle) {
            return [
                'id' => $circle->id,
                'type' => 'individual',
                'teacher' => [
                    'id' => $circle->quran_teacher_id,
                    'name' => $circle->quranTeacher?->name,
                    'avatar' => $circle->quranTeacher?->avatar
                        ? asset('storage/'.$circle->quranTeacher->avatar)
                        : null,
                ],
                'created_at' => $circle->created_at?->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Get student's group Quran circles.
     */
    protected function getGroupCircles(int $userId, int $academyId): array
    {
        // Get circles the student is enrolled in via pivot table
        $circles = QuranCircle::where('academy_id', $academyId)
            ->whereHas('students', function ($query) use ($userId) {
                $query->where('student_id', $userId);
            })
            ->with(['quranTeacher', 'students' => function ($query) use ($userId) {
                $query->where('student_id', $userId);
            }])
            ->get();

        return $circles->map(function ($circle) {
            $enrollment = $circle->students->first();

            return [
                'id' => $circle->id,
                'type' => 'group',
                'name' => $circle->name,
                'description' => $circle->description,
                'teacher' => [
                    'id' => $circle->quran_teacher_id,
                    'name' => $circle->quranTeacher?->name,
                    'avatar' => $circle->quranTeacher?->avatar
                        ? asset('storage/'.$circle->quranTeacher->avatar)
                        : null,
                ],
                'level' => $circle->level,
                'current_students' => $circle->current_students_count ?? 0,
                'max_students' => $circle->max_students,
                'schedule_days' => $circle->schedule_days ?? [],
                'start_time' => $circle->start_time,
                'end_time' => $circle->end_time,
                'enrollment_status' => $enrollment?->pivot?->status ?? 'enrolled',
                'enrolled_at' => $enrollment?->pivot?->enrolled_at
                    ? (is_string($enrollment->pivot->enrolled_at) ? $enrollment->pivot->enrolled_at : $enrollment->pivot->enrolled_at->toISOString())
                    : null,
            ];
        })->toArray();
    }

    /**
     * Get student's active subscriptions.
     */
    protected function getActiveSubscriptions(int $userId, int $academyId): array
    {
        $subscriptions = collect();

        // Get Quran subscriptions
        $quranSubs = QuranSubscription::where('student_id', $userId)
            ->where('academy_id', $academyId)
            ->where('status', 'active')
            ->with(['quranTeacherUser']) // Load the User directly
            ->get();

        foreach ($quranSubs as $sub) {
            $subscriptions->push([
                'id' => $sub->id,
                'type' => $sub->subscription_type === 'group' ? 'quran_group' : 'quran',
                'title' => $sub->package_name_ar ?? __('Quran Subscription'),
                'status' => $sub->status,
                'start_date' => $sub->starts_at?->toDateString(),
                'end_date' => $sub->ends_at?->toDateString(),
                'auto_renew' => $sub->auto_renew,
                'price' => $sub->final_price,
                'currency' => $sub->currency,
                'teacher' => [
                    'id' => $sub->quran_teacher_id,
                    'name' => $sub->quranTeacherUser?->name ?? __('Teacher'),
                    'avatar' => $sub->quranTeacherUser?->avatar
                        ? asset('storage/'.$sub->quranTeacherUser->avatar)
                        : null,
                ],
                'sessions' => [
                    'total' => $sub->total_sessions,
                    'used' => $sub->sessions_used,
                    'remaining' => $sub->sessions_remaining,
                ],
            ]);
        }

        // Get Academic subscriptions
        $academicSubs = AcademicSubscription::where('student_id', $userId)
            ->where('academy_id', $academyId)
            ->where('status', 'active')
            ->with(['academicTeacher.user']) // Load teacher with user
            ->get();

        foreach ($academicSubs as $sub) {
            $subscriptions->push([
                'id' => $sub->id,
                'type' => 'academic',
                'title' => $sub->package_name_ar ?? __('Academic Subscription'),
                'status' => $sub->status,
                'start_date' => $sub->starts_at?->toDateString(),
                'end_date' => $sub->ends_at?->toDateString(),
                'auto_renew' => $sub->auto_renew,
                'price' => $sub->final_price,
                'currency' => $sub->currency,
                'teacher' => [
                    'id' => $sub->teacher_id,
                    'name' => $sub->academicTeacher?->user?->name ?? __('Teacher'),
                    'avatar' => $sub->academicTeacher?->user?->avatar
                        ? asset('storage/'.$sub->academicTeacher->user->avatar)
                        : null,
                ],
                'sessions' => [
                    'total' => $sub->total_sessions,
                    'used' => $sub->sessions_used,
                    'remaining' => $sub->sessions_remaining,
                ],
            ]);
        }

        return $subscriptions->toArray();
    }
}
