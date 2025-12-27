<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Enums\SessionStatus;

class DashboardController extends Controller
{
    use ApiResponses;

    /**
     * Get teacher dashboard data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'is_quran_teacher' => $user->isQuranTeacher(),
                'is_academic_teacher' => $user->isAcademicTeacher(),
            ],
            'stats' => $this->getStats($user),
            'today_sessions' => $this->getTodaySessions($user),
            'upcoming_sessions' => $this->getUpcomingSessions($user),
            'recent_activity' => $this->getRecentActivity($user),
        ];

        return $this->success($data, __('Dashboard data retrieved successfully'));
    }

    /**
     * Get stats for teacher.
     */
    protected function getStats($user): array
    {
        $stats = [
            'total_students' => 0,
            'today_sessions' => 0,
            'upcoming_sessions' => 0,
            'completed_sessions_this_month' => 0,
        ];

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if ($quranTeacherId) {
                // Students from individual circles
                $individualStudents = QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->distinct('student_id')
                    ->count('student_id');

                $stats['total_students'] += $individualStudents;

                // Today's sessions
                $stats['today_sessions'] += QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->count();

                // Upcoming
                $stats['upcoming_sessions'] += QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->where('scheduled_at', '>', now())
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                    ->count();

                // Completed this month
                $stats['completed_sessions_this_month'] += QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->count();
            }
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                // Students
                $academicStudents = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->distinct('student_id')
                    ->count('student_id');

                $stats['total_students'] += $academicStudents;

                // Today's sessions
                $stats['today_sessions'] += AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->count();

                // Interactive courses
                $courseIds = $user->academicTeacherProfile->assignedCourses()
                    ->pluck('id');

                $stats['today_sessions'] += InteractiveCourseSession::whereIn('course_id', $courseIds)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->count();

                // Upcoming
                $stats['upcoming_sessions'] += AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->where('scheduled_at', '>', now())
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                    ->count();

                // Completed this month
                $stats['completed_sessions_this_month'] += AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->count();
            }
        }

        return $stats;
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
                        'description' => 'أكملت جلسة قرآنية مع ' . ($session->student?->user?->name ?? 'طالب'),
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
                        'description' => 'أكملت جلسة أكاديمية مع ' . ($session->student?->user?->name ?? 'طالب'),
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
