<?php

namespace App\Services\Unified;

use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * UnifiedStatisticsService
 *
 * PURPOSE:
 * Eliminates duplicate attendance/completion calculations scattered across services.
 * Provides a single, consistent way to calculate student statistics including:
 * - Attendance rates
 * - Session completion rates
 * - Subscription usage
 * - Learning progress
 *
 * USAGE:
 * $service = app(UnifiedStatisticsService::class);
 *
 * // Get comprehensive student statistics
 * $stats = $service->getStudentStatistics($studentId, $academyId);
 *
 * // Get attendance rate only
 * $rate = $service->getAttendanceRate($studentId, $academyId);
 *
 * // Get quick overview for dashboard
 * $overview = $service->getDashboardOverview($studentId, $academyId);
 *
 * CACHING:
 * Statistics are cached for 10 minutes to prevent excessive database queries.
 * Cache is automatically invalidated when sessions or subscriptions are updated.
 */
class UnifiedStatisticsService
{
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Get comprehensive statistics for a student
     *
     * @param int $studentId Student user ID
     * @param int $academyId Academy ID to scope to
     * @param bool $useCache Enable caching
     * @return array Comprehensive statistics
     */
    public function getStudentStatistics(
        int $studentId,
        int $academyId,
        bool $useCache = true
    ): array {
        $cacheKey = "unified_stats:student:{$studentId}:{$academyId}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $stats = [
            'sessions' => $this->getSessionStatistics($studentId, $academyId),
            'subscriptions' => $this->getSubscriptionStatistics($studentId, $academyId),
            'attendance' => $this->getAttendanceStatistics($studentId, $academyId),
            'progress' => $this->getProgressStatistics($studentId, $academyId),
            'calculated_at' => now()->toIso8601String(),
        ];

        if ($useCache) {
            Cache::put($cacheKey, $stats, self::CACHE_TTL);
        }

        return $stats;
    }

    /**
     * Get statistics for multiple students (useful for parent dashboards)
     */
    public function getStudentsStatistics(
        array $studentIds,
        int $academyId
    ): array {
        if (empty($studentIds)) {
            return [];
        }

        $result = [];
        foreach ($studentIds as $studentId) {
            $result[$studentId] = $this->getStudentStatistics($studentId, $academyId);
        }

        return $result;
    }

    /**
     * Get quick dashboard overview (lighter than full statistics)
     */
    public function getDashboardOverview(
        int $studentId,
        int $academyId
    ): array {
        $cacheKey = "unified_stats:overview:{$studentId}:{$academyId}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Quick counts using efficient queries
        $overview = [
            'active_subscriptions' => $this->countActiveSubscriptions($studentId, $academyId),
            'upcoming_sessions' => $this->countUpcomingSessions($studentId, $academyId),
            'completed_sessions_this_month' => $this->countCompletedSessionsThisMonth($studentId, $academyId),
            'overall_attendance_rate' => $this->getAttendanceRate($studentId, $academyId),
            'sessions_remaining' => $this->getTotalSessionsRemaining($studentId, $academyId),
        ];

        Cache::put($cacheKey, $overview, self::CACHE_TTL);

        return $overview;
    }

    /**
     * Get attendance rate as a percentage
     */
    public function getAttendanceRate(int $studentId, int $academyId): float
    {
        $totalSessions = $this->countTotalSessions($studentId, $academyId);
        $attendedSessions = $this->countAttendedSessions($studentId, $academyId);

        if ($totalSessions === 0) {
            return 0.0;
        }

        return round(($attendedSessions / $totalSessions) * 100, 1);
    }

    /**
     * Get attendance rate breakdown by session type
     */
    public function getAttendanceRateByType(int $studentId, int $academyId): array
    {
        return [
            'quran' => $this->getTypeAttendanceRate($studentId, $academyId, 'quran'),
            'academic' => $this->getTypeAttendanceRate($studentId, $academyId, 'academic'),
            'interactive' => $this->getTypeAttendanceRate($studentId, $academyId, 'interactive'),
        ];
    }

    // ========================================
    // SESSION STATISTICS
    // ========================================

    private function getSessionStatistics(int $studentId, int $academyId): array
    {
        $quranSessions = $this->getQuranSessionCounts($studentId, $academyId);
        $academicSessions = $this->getAcademicSessionCounts($studentId, $academyId);
        $interactiveSessions = $this->getInteractiveSessionCounts($studentId, $academyId);

        return [
            'quran' => $quranSessions,
            'academic' => $academicSessions,
            'interactive' => $interactiveSessions,
            'totals' => [
                'scheduled' => $quranSessions['scheduled'] + $academicSessions['scheduled'] + $interactiveSessions['scheduled'],
                'completed' => $quranSessions['completed'] + $academicSessions['completed'] + $interactiveSessions['completed'],
                'cancelled' => $quranSessions['cancelled'] + $academicSessions['cancelled'] + $interactiveSessions['cancelled'],
                'total' => $quranSessions['total'] + $academicSessions['total'] + $interactiveSessions['total'],
            ],
        ];
    }

    private function getQuranSessionCounts(int $studentId, int $academyId): array
    {
        $counts = QuranSession::query()
            ->where('academy_id', $academyId)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
            })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return $this->formatSessionCounts($counts);
    }

    private function getAcademicSessionCounts(int $studentId, int $academyId): array
    {
        $counts = AcademicSession::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return $this->formatSessionCounts($counts);
    }

    private function getInteractiveSessionCounts(int $studentId, int $academyId): array
    {
        $counts = InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) =>
                $q->where('student_id', $studentId)->where('status', 'enrolled')
            )
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return $this->formatSessionCounts($counts);
    }

    private function formatSessionCounts(array $counts): array
    {
        $scheduled = $counts[SessionStatus::SCHEDULED->value] ?? 0;
        $ongoing = $counts[SessionStatus::ONGOING->value] ?? 0;
        $completed = $counts[SessionStatus::COMPLETED->value] ?? 0;
        $cancelled = $counts[SessionStatus::CANCELLED->value] ?? 0;

        return [
            'scheduled' => $scheduled + $ongoing,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'total' => array_sum($counts),
        ];
    }

    // ========================================
    // SUBSCRIPTION STATISTICS
    // ========================================

    private function getSubscriptionStatistics(int $studentId, int $academyId): array
    {
        return [
            'quran' => $this->getQuranSubscriptionStats($studentId, $academyId),
            'academic' => $this->getAcademicSubscriptionStats($studentId, $academyId),
            'course' => $this->getCourseSubscriptionStats($studentId, $academyId),
            'totals' => [
                'active' => $this->countActiveSubscriptions($studentId, $academyId),
                'total' => $this->countTotalSubscriptions($studentId, $academyId),
            ],
        ];
    }

    private function getQuranSubscriptionStats(int $studentId, int $academyId): array
    {
        $subs = QuranSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->get();

        $active = $subs->where('status', SubscriptionStatus::ACTIVE);

        return [
            'total' => $subs->count(),
            'active' => $active->count(),
            'sessions_remaining' => $active->sum('sessions_remaining'),
            'sessions_used' => $active->sum('sessions_used'),
        ];
    }

    private function getAcademicSubscriptionStats(int $studentId, int $academyId): array
    {
        $subs = AcademicSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->get();

        $active = $subs->where('status', SubscriptionStatus::ACTIVE);

        return [
            'total' => $subs->count(),
            'active' => $active->count(),
            'sessions_remaining' => $active->sum(function ($sub) {
                return max(0, ($sub->total_sessions ?? 0) - ($sub->total_sessions_completed ?? 0));
            }),
            'sessions_completed' => $active->sum('total_sessions_completed'),
        ];
    }

    private function getCourseSubscriptionStats(int $studentId, int $academyId): array
    {
        $subs = CourseSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->get();

        $active = $subs->where('status', SubscriptionStatus::ACTIVE);

        return [
            'total' => $subs->count(),
            'active' => $active->count(),
            'recorded_courses' => $active->where('course_type', 'recorded')->count(),
            'interactive_courses' => $active->where('course_type', 'interactive')->count(),
            'total_lessons_completed' => $active->sum('completed_lessons'),
        ];
    }

    // ========================================
    // ATTENDANCE STATISTICS
    // ========================================

    private function getAttendanceStatistics(int $studentId, int $academyId): array
    {
        $rateByType = $this->getAttendanceRateByType($studentId, $academyId);

        return [
            'overall_rate' => $this->getAttendanceRate($studentId, $academyId),
            'by_type' => $rateByType,
            'this_week' => $this->getAttendanceRateThisWeek($studentId, $academyId),
            'this_month' => $this->getAttendanceRateThisMonth($studentId, $academyId),
        ];
    }

    private function getTypeAttendanceRate(int $studentId, int $academyId, string $type): float
    {
        $total = 0;
        $attended = 0;

        if ($type === 'quran') {
            $sessions = QuranSession::query()
                ->where('academy_id', $academyId)
                ->where(function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                        ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
                })
                ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
                ->get();

            $total = $sessions->count();
            $attended = $sessions->where('status', SessionStatus::COMPLETED)->count();
        } elseif ($type === 'academic') {
            $sessions = AcademicSession::query()
                ->where('academy_id', $academyId)
                ->where('student_id', $studentId)
                ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
                ->get();

            $total = $sessions->count();
            $attended = $sessions->where('status', SessionStatus::COMPLETED)->count();
        } elseif ($type === 'interactive') {
            $sessions = InteractiveCourseSession::query()
                ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
                ->whereHas('course.enrollments', fn ($q) =>
                    $q->where('student_id', $studentId)->where('status', 'enrolled')
                )
                ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
                ->get();

            $total = $sessions->count();
            $attended = $sessions->where('status', SessionStatus::COMPLETED)->count();
        }

        return $total > 0 ? round(($attended / $total) * 100, 1) : 0.0;
    }

    private function getAttendanceRateThisWeek(int $studentId, int $academyId): float
    {
        $startOfWeek = now()->startOfWeek();
        return $this->getAttendanceRateForPeriod($studentId, $academyId, $startOfWeek, now());
    }

    private function getAttendanceRateThisMonth(int $studentId, int $academyId): float
    {
        $startOfMonth = now()->startOfMonth();
        return $this->getAttendanceRateForPeriod($studentId, $academyId, $startOfMonth, now());
    }

    private function getAttendanceRateForPeriod(int $studentId, int $academyId, $from, $to): float
    {
        $total = 0;
        $attended = 0;

        // Quran sessions
        $quranSessions = QuranSession::query()
            ->where('academy_id', $academyId)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
            })
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
            ->get();

        $total += $quranSessions->count();
        $attended += $quranSessions->where('status', SessionStatus::COMPLETED)->count();

        // Academic sessions
        $academicSessions = AcademicSession::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
            ->get();

        $total += $academicSessions->count();
        $attended += $academicSessions->where('status', SessionStatus::COMPLETED)->count();

        // Interactive sessions
        $interactiveSessions = InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) =>
                $q->where('student_id', $studentId)->where('status', 'enrolled')
            )
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
            ->get();

        $total += $interactiveSessions->count();
        $attended += $interactiveSessions->where('status', SessionStatus::COMPLETED)->count();

        return $total > 0 ? round(($attended / $total) * 100, 1) : 0.0;
    }

    // ========================================
    // PROGRESS STATISTICS
    // ========================================

    private function getProgressStatistics(int $studentId, int $academyId): array
    {
        return [
            'quran' => $this->getQuranProgress($studentId, $academyId),
            'academic' => $this->getAcademicProgress($studentId, $academyId),
            'courses' => $this->getCoursesProgress($studentId, $academyId),
        ];
    }

    private function getQuranProgress(int $studentId, int $academyId): array
    {
        $activeSub = QuranSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();

        return [
            'current_surah' => $activeSub?->current_surah,
            'memorization_level' => $activeSub?->memorization_level,
            'sessions_completed' => $activeSub?->sessions_used ?? 0,
            'total_sessions' => $activeSub?->total_sessions ?? 0,
            'progress_percent' => $activeSub && $activeSub->total_sessions > 0
                ? round(($activeSub->sessions_used / $activeSub->total_sessions) * 100, 1)
                : 0,
        ];
    }

    private function getAcademicProgress(int $studentId, int $academyId): array
    {
        $activeSubs = AcademicSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->get();

        $totalCompleted = $activeSubs->sum('total_sessions_completed');
        $totalScheduled = $activeSubs->sum('total_sessions_scheduled');

        return [
            'subjects_count' => $activeSubs->count(),
            'sessions_completed' => $totalCompleted,
            'sessions_scheduled' => $totalScheduled,
            'average_grade' => null, // Could be calculated from reports
        ];
    }

    private function getCoursesProgress(int $studentId, int $academyId): array
    {
        $activeSubs = CourseSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->get();

        $recorded = $activeSubs->where('course_type', 'recorded');
        $interactive = $activeSubs->where('course_type', 'interactive');

        return [
            'recorded_courses' => [
                'enrolled' => $recorded->count(),
                'completed_lessons' => $recorded->sum('completed_lessons'),
                'total_lessons' => $recorded->sum('total_lessons'),
                'watch_time_minutes' => $recorded->sum('watch_time_minutes'),
            ],
            'interactive_courses' => [
                'enrolled' => $interactive->count(),
                'attendance_count' => $interactive->sum('attendance_count'),
                'average_grade' => $interactive->avg('final_grade'),
            ],
        ];
    }

    // ========================================
    // QUICK COUNT HELPERS
    // ========================================

    private function countActiveSubscriptions(int $studentId, int $academyId): int
    {
        $quran = QuranSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        $academic = AcademicSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        $course = CourseSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        return $quran + $academic + $course;
    }

    private function countTotalSubscriptions(int $studentId, int $academyId): int
    {
        $quran = QuranSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)->count();
        $academic = AcademicSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)->count();
        $course = CourseSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)->count();

        return $quran + $academic + $course;
    }

    private function countUpcomingSessions(int $studentId, int $academyId): int
    {
        $now = now();
        $endOfWeek = now()->addDays(7);

        $quran = QuranSession::where('academy_id', $academyId)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
            })
            ->where('status', SessionStatus::SCHEDULED)
            ->whereBetween('scheduled_at', [$now, $endOfWeek])
            ->count();

        $academic = AcademicSession::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SessionStatus::SCHEDULED)
            ->whereBetween('scheduled_at', [$now, $endOfWeek])
            ->count();

        $interactive = InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) =>
                $q->where('student_id', $studentId)->where('status', 'enrolled')
            )
            ->where('status', SessionStatus::SCHEDULED)
            ->whereBetween('scheduled_at', [$now, $endOfWeek])
            ->count();

        return $quran + $academic + $interactive;
    }

    private function countCompletedSessionsThisMonth(int $studentId, int $academyId): int
    {
        $startOfMonth = now()->startOfMonth();

        $quran = QuranSession::where('academy_id', $academyId)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
            })
            ->where('status', SessionStatus::COMPLETED)
            ->where('scheduled_at', '>=', $startOfMonth)
            ->count();

        $academic = AcademicSession::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SessionStatus::COMPLETED)
            ->where('scheduled_at', '>=', $startOfMonth)
            ->count();

        $interactive = InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) =>
                $q->where('student_id', $studentId)->where('status', 'enrolled')
            )
            ->where('status', SessionStatus::COMPLETED)
            ->where('scheduled_at', '>=', $startOfMonth)
            ->count();

        return $quran + $academic + $interactive;
    }

    private function countTotalSessions(int $studentId, int $academyId): int
    {
        $quran = QuranSession::where('academy_id', $academyId)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
            })
            ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
            ->count();

        $academic = AcademicSession::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
            ->count();

        $interactive = InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) =>
                $q->where('student_id', $studentId)->where('status', 'enrolled')
            )
            ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::CANCELLED])
            ->count();

        return $quran + $academic + $interactive;
    }

    private function countAttendedSessions(int $studentId, int $academyId): int
    {
        $quran = QuranSession::where('academy_id', $academyId)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereHas('circle.students', fn ($q) => $q->where('user_id', $studentId));
            })
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        $academic = AcademicSession::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        $interactive = InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) =>
                $q->where('student_id', $studentId)->where('status', 'enrolled')
            )
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        return $quran + $academic + $interactive;
    }

    private function getTotalSessionsRemaining(int $studentId, int $academyId): int
    {
        $quran = QuranSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->sum('sessions_remaining');

        $academic = AcademicSubscription::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->get()
            ->sum(function ($sub) {
                return max(0, ($sub->total_sessions ?? 0) - ($sub->total_sessions_completed ?? 0));
            });

        return $quran + $academic;
    }

    // ========================================
    // CACHE MANAGEMENT
    // ========================================

    /**
     * Clear statistics cache for a student
     */
    public function clearCacheForStudent(int $studentId, int $academyId): void
    {
        Cache::forget("unified_stats:student:{$studentId}:{$academyId}");
        Cache::forget("unified_stats:overview:{$studentId}:{$academyId}");
    }

    /**
     * Clear all statistics cache for an academy
     */
    public function clearCacheForAcademy(int $academyId): void
    {
        // Relies on TTL expiration without tagged cache
    }

    /**
     * Clear all unified statistics cache
     */
    public function clearAllCache(): void
    {
        // Relies on TTL expiration without tagged cache
    }
}
