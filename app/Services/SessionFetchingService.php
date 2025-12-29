<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SessionFetchingService
 *
 * Centralized service for fetching sessions across all session types.
 * Eliminates duplication of session query logic across controllers.
 *
 * Used by:
 * - Api\V1\Student\DashboardController
 * - Api\V1\ParentApi\DashboardController
 * - ParentDashboardService
 * - Other dashboard and calendar components
 */
class SessionFetchingService
{
    /**
     * Get today's sessions for a user across all session types.
     *
     * @param int $userId User ID to fetch sessions for
     * @param Carbon|null $today Today's date (defaults to Carbon::today())
     * @return array Array of ['type' => 'quran|academic|interactive', 'session' => Model]
     */
    public function getTodaySessions(int $userId, ?Carbon $today = null): array
    {
        $today = $today ?? Carbon::today();
        $sessions = [];

        // Fetch Quran sessions
        $quranSessions = QuranSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['quranTeacher', 'individualCircle', 'circle'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = [
                'type' => 'quran',
                'session' => $session,
            ];
        }

        // Fetch Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'type' => 'academic',
                'session' => $session,
            ];
        }

        // Fetch Interactive course sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = [
                'type' => 'interactive',
                'session' => $session,
            ];
        }

        // Sort all sessions by scheduled_at
        usort($sessions, function ($a, $b) {
            return $a['session']->scheduled_at <=> $b['session']->scheduled_at;
        });

        return $sessions;
    }

    /**
     * Get upcoming sessions for a user (next 7 days, excluding today).
     *
     * @param int $userId User ID to fetch sessions for
     * @param Carbon|null $today Today's date (defaults to Carbon::today())
     * @param int $days Number of days to look ahead (default 7)
     * @param int $limit Maximum number of sessions to return (default 10)
     * @return array Array of ['type' => 'quran|academic|interactive', 'session' => Model]
     */
    public function getUpcomingSessions(int $userId, ?Carbon $today = null, int $days = 7, int $limit = 10): array
    {
        $today = $today ?? Carbon::today();
        $endDate = $today->copy()->addDays($days);
        $sessions = [];

        // Fetch Quran sessions
        $quranSessions = QuranSession::where('student_id', $userId)
            ->whereDate('scheduled_at', '>', $today)
            ->whereDate('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = [
                'type' => 'quran',
                'session' => $session,
            ];
        }

        // Fetch Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->whereDate('scheduled_at', '>', $today)
            ->whereDate('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['academicTeacher.user'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'type' => 'academic',
                'session' => $session,
            ];
        }

        // Fetch Interactive course sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->whereDate('scheduled_at', '>', $today)
            ->whereDate('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = [
                'type' => 'interactive',
                'session' => $session,
            ];
        }

        // Sort and limit
        usort($sessions, function ($a, $b) {
            return $a['session']->scheduled_at <=> $b['session']->scheduled_at;
        });

        return array_slice($sessions, 0, $limit);
    }

    /**
     * Get upcoming sessions for multiple users (optimized for parent dashboard).
     *
     * @param array $userIds Array of user IDs
     * @param Carbon|null $now Starting date/time (defaults to now())
     * @param int $days Number of days to look ahead (default 7)
     * @return array Array of formatted session data
     */
    public function getAllChildrenUpcomingSessions(array $userIds, ?Carbon $now = null, int $days = 7): array
    {
        $now = $now ?? now();
        $endDate = $now->copy()->addDays($days);
        $sessions = [];

        // Fetch all Quran sessions at once (avoid N+1)
        $quranSessions = QuranSession::whereIn('student_id', $userIds)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['student.user', 'quranTeacher'])
            ->orderBy('scheduled_at')
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

        // Fetch all Academic sessions at once (avoid N+1)
        $academicSessions = AcademicSession::whereIn('student_id', $userIds)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['student.user', 'academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
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

        // Sort by time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $sessions;
    }

    /**
     * Get today's sessions count for a child (optimized for parent dashboard).
     *
     * @param int $userId User ID
     * @param Carbon|null $today Today's date
     * @return int Total count of today's sessions
     */
    public function getTodaySessionsCount(int $userId, ?Carbon $today = null): int
    {
        $today = $today ?? Carbon::today();

        $quranCount = QuranSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value])
            ->count();

        $academicCount = AcademicSession::where('student_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value])
            ->count();

        $interactiveCount = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value])
            ->count();

        return $quranCount + $academicCount + $interactiveCount;
    }

    /**
     * Get recent sessions for a user.
     *
     * @param int $userId User ID
     * @param int $days Number of days to look back (default 7)
     * @param int $limit Maximum number of sessions
     * @return array Array of sessions
     */
    public function getRecentSessions(int $userId, int $days = 7, int $limit = 10): array
    {
        $startDate = Carbon::today()->subDays($days);
        $sessions = [];

        // Fetch Quran sessions
        $quranSessions = QuranSession::where('student_id', $userId)
            ->where('scheduled_at', '>=', $startDate)
            ->where('scheduled_at', '<=', now())
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = [
                'type' => 'quran',
                'session' => $session,
            ];
        }

        // Fetch Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->where('scheduled_at', '>=', $startDate)
            ->where('scheduled_at', '<=', now())
            ->with(['academicTeacher.user'])
            ->orderBy('scheduled_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'type' => 'academic',
                'session' => $session,
            ];
        }

        // Fetch Interactive sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('student_id', $userId);
        })
            ->where('scheduled_at', '>=', $startDate)
            ->where('scheduled_at', '<=', now())
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = [
                'type' => 'interactive',
                'session' => $session,
            ];
        }

        // Sort by date (most recent first)
        usort($sessions, function ($a, $b) {
            return $b['session']->scheduled_at <=> $a['session']->scheduled_at;
        });

        return array_slice($sessions, 0, $limit);
    }
}
