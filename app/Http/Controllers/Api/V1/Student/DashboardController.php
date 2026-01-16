<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
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

        // Get recent homework/quizzes count
        $pendingHomework = $this->getPendingHomeworkCount($user->id);
        $pendingQuizzes = $this->getPendingQuizzesCount($user->id);

        // Get unread notifications count
        $unreadNotifications = $user->unreadNotifications()->count();

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
        ];

        return $this->success(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }

    /**
     * Get pending homework count.
     */
    protected function getPendingHomeworkCount(int $userId): int
    {
        return AcademicSession::where('student_id', $userId)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->whereDoesntHave('homeworkSubmissions', function ($q) use ($userId) {
                $q->where('student_id', $userId);
            })
            ->count();
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
    protected function formatUnifiedSessionsForApi(\Illuminate\Support\Collection $sessions): array
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
}
