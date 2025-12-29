<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use App\Http\Resources\Api\V1\Student\DashboardResource;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSubscription;
use App\Services\SessionFetchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

/**
 * Student Dashboard API Controller
 *
 * Demonstrates usage of the standardized ApiResponseService via ApiResponses trait.
 * Provides dashboard data including sessions, subscriptions, and quick stats.
 */
class DashboardController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected SessionFetchingService $sessionFetchingService
    ) {
    }

    /**
     * Get student dashboard data.
     *
     * Demonstrates ApiResponseService usage:
     * - notFoundResponse() for missing student profile
     * - successResponse() for successful data retrieval
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $studentProfile = $user->studentProfile()->first();

        // Example: Using notFoundResponse() method from ApiResponses trait
        if (!$studentProfile) {
            return $this->notFoundResponse(__('Student profile not found.'));
        }

        // Get today's date
        $today = Carbon::today();

        // Get today's sessions across all types
        $todaySessions = $this->sessionFetchingService->getTodaySessions($user->id, $today);

        // Get upcoming sessions (next 7 days, excluding today)
        $upcomingSessions = $this->sessionFetchingService->getUpcomingSessions($user->id, $today);

        // Get active subscriptions count
        $activeSubscriptions = $this->getActiveSubscriptionsCount($user->id);

        // Get recent homework/quizzes count
        $pendingHomework = $this->getPendingHomeworkCount($user->id);
        $pendingQuizzes = $this->getPendingQuizzesCount($user->id);

        // Get unread notifications count
        $unreadNotifications = $user->unreadNotifications()->count();

        // Get quick stats
        $stats = [
            'today_sessions' => count($todaySessions),
            'upcoming_sessions' => count($upcomingSessions),
            'active_subscriptions' => $activeSubscriptions,
            'pending_homework' => $pendingHomework,
            'pending_quizzes' => $pendingQuizzes,
            'unread_notifications' => $unreadNotifications,
        ];

        $dashboardData = [
            'student' => [
                'id' => $studentProfile->id,
                'name' => $studentProfile->full_name,
                'student_code' => $studentProfile->student_code,
                'avatar' => $studentProfile->avatar ? asset('storage/' . $studentProfile->avatar) : null,
                'grade_level' => $studentProfile->gradeLevel?->name,
            ],
            'stats' => $stats,
            'today_sessions' => $this->formatSessionsForDashboard($todaySessions),
            'upcoming_sessions' => $this->formatSessionsForDashboard($upcomingSessions),
        ];

        // Example: Using successResponse() method from ApiResponses trait
        // Automatically includes success flag, message, and data in standardized format
        return $this->successResponse(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }

    /**
     * Get active subscriptions count.
     */
    protected function getActiveSubscriptionsCount(int $userId): int
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
     * Get pending homework count.
     */
    protected function getPendingHomeworkCount(int $userId): int
    {
        // Get academic sessions with pending homework
        return AcademicSession::where('student_id', $userId)
            ->whereNotNull('homework')
            ->where('homework', '!=', '')
            ->whereDoesntHave('homeworkSubmissions', function ($q) use ($userId) {
                $q->where('student_id', $userId);
            })
            ->count();
    }

    /**
     * Get pending quizzes count.
     */
    protected function getPendingQuizzesCount(int $userId): int
    {
        return \App\Models\QuizAssignment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->count();
    }

    /**
     * Format sessions for dashboard display.
     */
    protected function formatSessionsForDashboard(array $sessions): array
    {
        return array_map(function ($item) {
            $session = $item['session'];
            $type = $item['type'];

            $baseData = [
                'id' => $session->id,
                'type' => $type,
                'title' => $this->getSessionTitle($session, $type),
                'status' => $session->status->value ?? $session->status,
                'duration_minutes' => $session->duration_minutes ?? 45,
                'can_join' => $this->canJoinSession($session, $type),
            ];

            // Add scheduled time (all session types now use scheduled_at)
            $baseData['scheduled_at'] = $session->scheduled_at?->toISOString();

            // Add teacher info
            $teacher = $this->getTeacherFromSession($session, $type);
            if ($teacher) {
                $baseData['teacher'] = [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'avatar' => $teacher->avatar ? asset('storage/' . $teacher->avatar) : null,
                ];
            }

            return $baseData;
        }, $sessions);
    }

    /**
     * Get session title.
     */
    protected function getSessionTitle($session, string $type): string
    {
        return match ($type) {
            'quran' => $session->title ?? 'جلسة قرآنية',
            'academic' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
            'interactive' => $session->title ?? $session->course?->title ?? 'جلسة تفاعلية',
            default => 'جلسة',
        };
    }

    /**
     * Get teacher from session.
     */
    protected function getTeacherFromSession($session, string $type)
    {
        return match ($type) {
            // quranTeacher returns User directly (not QuranTeacherProfile)
            'quran' => $session->quranTeacher,
            // academicTeacher returns AcademicTeacherProfile, so we need ->user
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };
    }

    /**
     * Check if student can join session.
     */
    protected function canJoinSession($session, string $type): bool
    {
        $now = now();
        $sessionTime = $session->scheduled_at;

        if (!$sessionTime) {
            return false;
        }

        // Can join 10 minutes before until session end
        $joinStart = $sessionTime->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 45;
        $joinEnd = $sessionTime->copy()->addMinutes($duration);

        return $now->between($joinStart, $joinEnd)
            && !in_array($session->status->value ?? $session->status, [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]);
    }
}
