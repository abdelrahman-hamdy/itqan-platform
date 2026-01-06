<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Teacher Dashboard API Controller
 *
 * Demonstrates usage of the standardized ApiResponseService via ApiResponses trait.
 * Supports both Quran and Academic teacher dashboards.
 */
class DashboardController extends Controller
{
    use ApiResponses;

    /**
     * Get teacher dashboard data.
     *
     * Demonstrates ApiResponseService usage:
     * - successResponse() with comprehensive teacher data
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $dashboardData = [
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
                'is_quran_teacher' => $user->isQuranTeacher(),
                'is_academic_teacher' => $user->isAcademicTeacher(),
            ],
            'stats' => $this->getStats($user),
            'today_sessions' => $this->getTodaySessions($user),
            'upcoming_sessions' => $this->getUpcomingSessions($user),
            'recent_activity' => $this->getRecentActivity($user),
        ];

        // Example: Using successResponse() from ApiResponses trait
        return $this->success(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }

    /**
     * Get stats for teacher with optimized queries and caching.
     *
     * Uses aggregated queries instead of multiple COUNT queries.
     * Results are cached for 5 minutes to reduce database load.
     */
    protected function getStats($user): array
    {
        $cacheKey = "teacher_dashboard_stats_{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            $stats = [
                'total_students' => 0,
                'today_sessions' => 0,
                'upcoming_sessions' => 0,
                'completed_sessions_this_month' => 0,
            ];

            $today = Carbon::today()->toDateString();
            $monthStart = Carbon::now()->startOfMonth()->toDateTimeString();
            $monthEnd = Carbon::now()->endOfMonth()->toDateTimeString();
            $now = now()->toDateTimeString();
            $cancelledStatus = SessionStatus::CANCELLED->value;
            $completedStatus = SessionStatus::COMPLETED->value;

            if ($user->isQuranTeacher()) {
                $quranTeacherId = $user->quranTeacherProfile?->id;

                if ($quranTeacherId) {
                    // Single aggregated query for all Quran stats
                    $quranStats = QuranSession::where('quran_teacher_id', $quranTeacherId)
                        ->selectRaw('
                            COUNT(DISTINCT student_id) as total_students,
                            SUM(CASE WHEN DATE(scheduled_at) = ? AND status != ? THEN 1 ELSE 0 END) as today_sessions,
                            SUM(CASE WHEN scheduled_at > ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) as upcoming_sessions,
                            SUM(CASE WHEN scheduled_at BETWEEN ? AND ? AND status = ? THEN 1 ELSE 0 END) as completed_this_month
                        ', [$today, $cancelledStatus, $now, $cancelledStatus, $completedStatus, $monthStart, $monthEnd, $completedStatus])
                        ->first();

                    if ($quranStats) {
                        $stats['total_students'] += (int) $quranStats->total_students;
                        $stats['today_sessions'] += (int) $quranStats->today_sessions;
                        $stats['upcoming_sessions'] += (int) $quranStats->upcoming_sessions;
                        $stats['completed_sessions_this_month'] += (int) $quranStats->completed_this_month;
                    }
                }
            }

            if ($user->isAcademicTeacher()) {
                $academicTeacherId = $user->academicTeacherProfile?->id;

                if ($academicTeacherId) {
                    // Single aggregated query for Academic session stats
                    $academicStats = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                        ->selectRaw('
                            COUNT(DISTINCT student_id) as total_students,
                            SUM(CASE WHEN DATE(scheduled_at) = ? AND status != ? THEN 1 ELSE 0 END) as today_sessions,
                            SUM(CASE WHEN scheduled_at > ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) as upcoming_sessions,
                            SUM(CASE WHEN scheduled_at BETWEEN ? AND ? AND status = ? THEN 1 ELSE 0 END) as completed_this_month
                        ', [$today, $cancelledStatus, $now, $cancelledStatus, $completedStatus, $monthStart, $monthEnd, $completedStatus])
                        ->first();

                    if ($academicStats) {
                        $stats['total_students'] += (int) $academicStats->total_students;
                        $stats['today_sessions'] += (int) $academicStats->today_sessions;
                        $stats['upcoming_sessions'] += (int) $academicStats->upcoming_sessions;
                        $stats['completed_sessions_this_month'] += (int) $academicStats->completed_this_month;
                    }

                    // Interactive course sessions (separate query as it uses different teacher relationship)
                    $courseIds = $user->academicTeacherProfile->assignedCourses()->pluck('id');

                    if ($courseIds->isNotEmpty()) {
                        $interactiveStats = InteractiveCourseSession::whereIn('course_id', $courseIds)
                            ->selectRaw('
                                SUM(CASE WHEN DATE(scheduled_at) = ? AND status != ? THEN 1 ELSE 0 END) as today_sessions
                            ', [$today, $cancelledStatus])
                            ->first();

                        if ($interactiveStats) {
                            $stats['today_sessions'] += (int) $interactiveStats->today_sessions;
                        }
                    }
                }
            }

            return $stats;
        });
    }

    /**
     * Get today's sessions.
     */
    protected function getTodaySessions($user): array
    {
        $today = Carbon::today();
        $sessions = [];

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if ($quranTeacherId) {
                $quranSessions = QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['student.user', 'individualCircle', 'circle'])
                    ->orderBy('scheduled_at')
                    ->get();

                foreach ($quranSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'quran',
                        'title' => $session->title ?? 'جلسة قرآنية',
                        'student_name' => $session->student?->user?->name ?? $session->student?->full_name,
                        'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'status' => $session->status->value ?? $session->status,
                        'duration_minutes' => $session->duration_minutes ?? 60,
                    ];
                }
            }
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['student.user', 'academicSubscription'])
                    ->orderBy('scheduled_at')
                    ->get();

                foreach ($academicSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'academic',
                        'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                        'student_name' => $session->student?->user?->name ?? 'طالب',
                        'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'status' => $session->status->value ?? $session->status,
                        'duration_minutes' => $session->duration_minutes ?? 60,
                    ];
                }

                // Interactive course sessions
                $courseIds = $user->academicTeacherProfile->assignedCourses()
                    ->pluck('id');

                $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $courseIds)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['course'])
                    ->orderBy('scheduled_at')
                    ->get();

                foreach ($interactiveSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'interactive',
                        'title' => $session->title ?? $session->course?->title,
                        'course_name' => $session->course?->title,
                        'session_number' => $session->session_number,
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'status' => $session->status->value ?? $session->status,
                        'duration_minutes' => $session->duration_minutes ?? 60,
                    ];
                }
            }
        }

        // Sort by scheduled time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $sessions;
    }

    /**
     * Get upcoming sessions (next 7 days).
     */
    protected function getUpcomingSessions($user): array
    {
        $now = now();
        $endDate = $now->copy()->addDays(7);
        $sessions = [];

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if ($quranTeacherId) {
                $quranSessions = QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->where('scheduled_at', '>', $now)
                    ->where('scheduled_at', '<=', $endDate)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                    ->with(['student.user'])
                    ->orderBy('scheduled_at')
                    ->limit(5)
                    ->get();

                foreach ($quranSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'quran',
                        'title' => $session->title ?? 'جلسة قرآنية',
                        'student_name' => $session->student?->user?->name ?? $session->student?->full_name,
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'status' => $session->status->value ?? $session->status,
                    ];
                }
            }
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->where('scheduled_at', '>', $now)
                    ->where('scheduled_at', '<=', $endDate)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                    ->with(['student.user', 'academicSubscription'])
                    ->orderBy('scheduled_at')
                    ->limit(5)
                    ->get();

                foreach ($academicSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'academic',
                        'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                        'student_name' => $session->student?->user?->name ?? 'طالب',
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'status' => $session->status->value ?? $session->status,
                    ];
                }
            }
        }

        // Sort by scheduled time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return array_slice($sessions, 0, 5);
    }

    /**
     * Get recent activity.
     */
    protected function getRecentActivity($user): array
    {
        $activities = [];

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if ($quranTeacherId) {
                $recentQuran = QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->with(['student.user'])
                    ->orderBy('ended_at', 'desc')
                    ->limit(3)
                    ->get();

                foreach ($recentQuran as $session) {
                    $activities[] = [
                        'type' => 'session_completed',
                        'session_type' => 'quran',
                        'session_id' => $session->id,
                        'description' => 'أكملت جلسة قرآنية مع '.($session->student?->user?->name ?? 'طالب'),
                        'timestamp' => $session->ended_at?->toISOString() ?? $session->updated_at->toISOString(),
                    ];
                }
            }
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                $recentAcademic = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->with(['student.user'])
                    ->orderBy('ended_at', 'desc')
                    ->limit(3)
                    ->get();

                foreach ($recentAcademic as $session) {
                    $activities[] = [
                        'type' => 'session_completed',
                        'session_type' => 'academic',
                        'session_id' => $session->id,
                        'description' => 'أكملت جلسة أكاديمية مع '.($session->student?->user?->name ?? 'طالب'),
                        'timestamp' => $session->ended_at?->toISOString() ?? $session->updated_at->toISOString(),
                    ];
                }
            }
        }

        // Sort by timestamp desc
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 5);
    }
}
