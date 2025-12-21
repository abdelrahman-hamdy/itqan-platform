<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Student\DashboardResource;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    use ApiResponses;

    /**
     * Get student dashboard data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? app('current_academy');
        $studentProfile = $user->studentProfile()->first();

        if (!$studentProfile) {
            return $this->error(
                __('Student profile not found.'),
                404,
                'STUDENT_PROFILE_NOT_FOUND'
            );
        }

        // Get today's date
        $today = Carbon::today();

        // Get today's sessions across all types
        $todaySessions = $this->getTodaySessions($user->id, $today);

        // Get upcoming sessions (next 7 days, excluding today)
        $upcomingSessions = $this->getUpcomingSessions($user->id, $today);

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

        return $this->success([
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
        ], __('Dashboard data retrieved successfully'));
    }

    /**
     * Get today's sessions for the student.
     */
    protected function getTodaySessions(int $userId, Carbon $today): array
    {
        $sessions = [];

        // Quran sessions
        // Note: quranTeacher relationship returns User directly, not QuranTeacherProfile
        $quranSessions = QuranSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['quranTeacher', 'individualCircle', 'circle'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = [
                'type' => 'quran',
                'session' => $session,
            ];
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'type' => 'academic',
                'session' => $session,
            ];
        }

        // Interactive course sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = [
                'type' => 'interactive',
                'session' => $session,
            ];
        }

        // Sort all sessions by time (all session types now use scheduled_at)
        usort($sessions, function ($a, $b) {
            return $a['session']->scheduled_at <=> $b['session']->scheduled_at;
        });

        return $sessions;
    }

    /**
     * Get upcoming sessions for the student (next 7 days).
     */
    protected function getUpcomingSessions(int $userId, Carbon $today): array
    {
        $sessions = [];
        $endDate = $today->copy()->addDays(7);

        // Quran sessions
        // Note: quranTeacher relationship returns User directly, not QuranTeacherProfile
        $quranSessions = QuranSession::where('student_id', $userId)
            ->whereDate('scheduled_at', '>', $today)
            ->whereDate('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = [
                'type' => 'quran',
                'session' => $session,
            ];
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->whereDate('scheduled_at', '>', $today)
            ->whereDate('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['academicTeacher.user'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'type' => 'academic',
                'session' => $session,
            ];
        }

        // Interactive course sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->whereDate('scheduled_at', '>', $today)
            ->whereDate('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = [
                'type' => 'interactive',
                'session' => $session,
            ];
        }

        // Sort and limit (all session types now use scheduled_at)
        usort($sessions, function ($a, $b) {
            return $a['session']->scheduled_at <=> $b['session']->scheduled_at;
        });

        return array_slice($sessions, 0, 10);
    }

    /**
     * Get active subscriptions count.
     */
    protected function getActiveSubscriptionsCount(int $userId): int
    {
        $count = 0;

        $count += QuranSubscription::where('student_id', $userId)
            ->where('status', 'active')
            ->count();

        $count += AcademicSubscription::where('student_id', $userId)
            ->where('status', 'active')
            ->count();

        $count += CourseSubscription::where('student_id', $userId)
            ->where('status', 'active')
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
            && !in_array($session->status->value ?? $session->status, ['cancelled', 'completed']);
    }
}
