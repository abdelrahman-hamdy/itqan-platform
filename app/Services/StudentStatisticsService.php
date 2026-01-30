<?php

namespace App\Services;

use App\Contracts\StudentStatisticsServiceInterface;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionAttendance;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSessionAttendance;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for calculating student statistics.
 *
 * Extracted from StudentProfileController::calculateStudentStats() (~292 lines).
 * Provides comprehensive statistics for the student dashboard.
 */
class StudentStatisticsService implements StudentStatisticsServiceInterface
{
    /**
     * Calculate all statistics for a student.
     *
     * @param  User  $user  The student user
     * @return array Complete statistics data
     */
    public function calculate(User $user): array
    {
        $cacheKey = "student:stats:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            $academy = $user->academy;
            $studentProfile = $user->studentProfileUnscoped;
            $studentId = $studentProfile?->id;

            // Get next session info
            $nextSessionData = $this->getNextSessionInfo($user, $academy, $studentId);

            // Calculate homework and quizzes
            $pendingHomework = $this->countPendingHomework($user, $academy);
            $pendingQuizzes = $this->countPendingQuizzes($studentId);

            // Calculate attendance
            $attendanceRate = $this->calculateAttendanceRate($user, $academy);

            // Calculate today's learning time
            $todayLearning = $this->calculateTodayLearning($user, $academy, $studentId);

            // Count completed sessions
            $completedSessions = $this->countCompletedSessions($user, $academy, $studentId);

            // Count active courses
            $activeCourses = $this->countActiveCourses($user, $academy, $studentId);

            // Calculate Quran progress
            $quranStats = $this->calculateQuranProgress($user, $academy);

            return [
                // Session info
                'nextSessionText' => $nextSessionData['text'],
                'nextSessionIcon' => $nextSessionData['icon'],
                'nextSessionDate' => $nextSessionData['date'],

                // Tasks
                'pendingHomework' => $pendingHomework,
                'pendingQuizzes' => $pendingQuizzes,

                // Time tracking
                'todayLearningHours' => $todayLearning['hours'],
                'todayLearningMinutes' => $todayLearning['minutes'],

                // Performance
                'attendanceRate' => $attendanceRate,
                'totalCompletedSessions' => $completedSessions['total'],

                // Courses
                'activeCourses' => $activeCourses['total'],
                'activeInteractiveCourses' => $activeCourses['interactive'],
                'activeRecordedCourses' => $activeCourses['recorded'],

                // Quran
                'quranProgress' => round($quranStats['progress'], 1),
                'quranPages' => $quranStats['pages'],
                'quranTrialRequestsCount' => $quranStats['trialRequests'],
                'activeQuranSubscriptions' => $quranStats['activeSubscriptions'],
                'quranCirclesCount' => $quranStats['circlesCount'],
            ];
        });
    }

    /**
     * Get next upcoming session information.
     */
    protected function getNextSessionInfo(User $user, $academy, ?int $studentId): array
    {
        $nextSession = null;
        $sessionType = null;

        // Check Quran sessions
        $nextQuranSession = QuranSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->first();

        // Check Academic sessions
        $nextAcademicSession = AcademicSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->first();

        // Check Interactive course sessions
        $nextInteractiveSession = null;
        if ($studentId) {
            $nextInteractiveSession = InteractiveCourseSession::whereHas('course.enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
                ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->first();
        }

        // Find the nearest upcoming session
        $sessions = collect([
            ['session' => $nextQuranSession, 'type' => 'quran'],
            ['session' => $nextAcademicSession, 'type' => 'academic'],
            ['session' => $nextInteractiveSession, 'type' => 'interactive'],
        ])->filter(fn ($s) => $s['session'] !== null)
            ->sortBy(fn ($s) => $s['session']->scheduled_at)
            ->first();

        if ($sessions) {
            $nextSession = $sessions['session'];
            $sessionType = $sessions['type'];
        }

        // Format the response
        if (! $nextSession) {
            return [
                'text' => 'لا توجد جلسات قادمة',
                'icon' => 'heroicon-o-calendar',
                'date' => null,
            ];
        }

        $icons = [
            'quran' => 'heroicon-o-book-open',
            'academic' => 'heroicon-o-academic-cap',
            'interactive' => 'heroicon-o-video-camera',
        ];

        return [
            'text' => $this->formatSessionTimeText($nextSession->scheduled_at),
            'icon' => $icons[$sessionType] ?? 'heroicon-o-calendar',
            'date' => $nextSession->scheduled_at,
        ];
    }

    /**
     * Format session time as human-readable text.
     */
    protected function formatSessionTimeText($scheduledAt): string
    {
        $diffInHours = now()->diffInHours($scheduledAt, false);

        if ($diffInHours < 1) {
            $minutes = now()->diffInMinutes($scheduledAt, false);

            return "خلال {$minutes} دقيقة";
        } elseif ($diffInHours < 24) {
            return "خلال {$diffInHours} ساعة";
        } else {
            $days = now()->diffInDays($scheduledAt, false);

            return "خلال {$days} يوم";
        }
    }

    /**
     * Count pending homework across all session types.
     */
    protected function countPendingHomework(User $user, $academy): int
    {
        // Pending Quran homework
        $pendingQuran = QuranSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->where(function ($query) {
                $query->where('homework_assigned', true)
                    ->orWhereNotNull('homework_details');
            })
            ->whereDoesntHave('studentReports', function ($query) use ($user) {
                $query->where('student_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNotNull('new_memorization_degree')
                            ->orWhereNotNull('reservation_degree');
                    });
            })
            ->count();

        // Pending Academic homework
        $pendingAcademic = AcademicSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->where(function ($query) {
                $query->where('homework_assigned', true)
                    ->orWhereNotNull('homework_description');
            })
            ->whereDoesntHave('studentReports', function ($query) use ($user) {
                $query->where('student_id', $user->id)
                    ->whereNotNull('homework_completion_degree');
            })
            ->count();

        return $pendingQuran + $pendingAcademic;
    }

    /**
     * Count pending quizzes.
     */
    protected function countPendingQuizzes(?int $studentId): int
    {
        if (! $studentId) {
            return 0;
        }

        try {
            return QuizAssignment::where('is_visible', true)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('available_from')
                            ->orWhere('available_from', '<=', now());
                    })->where(function ($q) {
                        $q->whereNull('available_until')
                            ->orWhere('available_until', '>=', now());
                    });
                })
                ->where('assignable_type', 'App\\Models\\InteractiveCourse')
                ->whereHas('assignable', function ($query) use ($studentId) {
                    $query->whereHas('enrollments', function ($enrollQuery) use ($studentId) {
                        $enrollQuery->where('student_id', $studentId)
                            ->whereIn('enrollment_status', ['enrolled', 'completed']);
                    });
                })
                ->whereDoesntHave('attempts', function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                        ->whereNotNull('submitted_at');
                })
                ->count();
        } catch (\Exception $e) {
            Log::warning('Error counting pending quizzes', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Calculate overall attendance rate.
     */
    protected function calculateAttendanceRate(User $user, $academy): int
    {
        $cacheKey = "student:attendance_rate:{$user->id}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $academy) {
            $presentStatuses = [
                AttendanceStatus::ATTENDED->value,
                AttendanceStatus::LATE->value,
                AttendanceStatus::LEFT->value,
            ];

            // Quran attendance
            $quranTotal = QuranSessionAttendance::where('student_id', $user->id)
                ->whereHas('session', fn ($q) => $q->where('academy_id', $academy->id))
                ->count();

            $quranPresent = QuranSessionAttendance::where('student_id', $user->id)
                ->whereIn('attendance_status', $presentStatuses)
                ->whereHas('session', fn ($q) => $q->where('academy_id', $academy->id))
                ->count();

            // Academic attendance
            $academicTotal = AcademicSessionAttendance::where('student_id', $user->id)
                ->whereHas('session', fn ($q) => $q->where('academy_id', $academy->id))
                ->count();

            $academicPresent = AcademicSessionAttendance::where('student_id', $user->id)
                ->whereIn('attendance_status', $presentStatuses)
                ->whereHas('session', fn ($q) => $q->where('academy_id', $academy->id))
                ->count();

            $total = $quranTotal + $academicTotal;
            $present = $quranPresent + $academicPresent;

            return $total > 0 ? (int) round(($present / $total) * 100) : 0;
        });
    }

    /**
     * Calculate today's learning time.
     */
    protected function calculateTodayLearning(User $user, $academy, ?int $studentId): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $validStatuses = [
            SessionStatus::SCHEDULED,
            SessionStatus::READY,
            SessionStatus::ONGOING,
            SessionStatus::COMPLETED,
        ];

        // Quran sessions today
        $quranMinutes = QuranSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->whereIn('status', $validStatuses)
            ->sum('duration_minutes');

        // Academic sessions today
        $academicMinutes = AcademicSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->whereIn('status', $validStatuses)
            ->sum('duration_minutes');

        // Interactive sessions today
        $interactiveMinutes = 0;
        if ($studentId) {
            $interactiveMinutes = InteractiveCourseSession::whereHas('course.enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
                ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
                ->whereIn('status', $validStatuses)
                ->sum('duration_minutes');
        }

        $totalMinutes = $quranMinutes + $academicMinutes + $interactiveMinutes;

        return [
            'minutes' => $totalMinutes,
            'hours' => round($totalMinutes / 60, 1),
        ];
    }

    /**
     * Count completed sessions across all types.
     */
    protected function countCompletedSessions(User $user, $academy, ?int $studentId): array
    {
        $quran = QuranSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        $academic = AcademicSession::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        $interactive = 0;
        if ($studentId) {
            $interactive = InteractiveCourseSession::whereHas('course.enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
                ->where('status', SessionStatus::COMPLETED)
                ->count();
        }

        return [
            'quran' => $quran,
            'academic' => $academic,
            'interactive' => $interactive,
            'total' => $quran + $academic + $interactive,
        ];
    }

    /**
     * Count active courses.
     */
    protected function countActiveCourses(User $user, $academy, ?int $studentId): array
    {
        $interactive = 0;
        if ($studentId) {
            $interactive = InteractiveCourse::where('academy_id', $academy->id)
                ->whereHas('enrollments', function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                        ->whereIn('enrollment_status', ['enrolled', 'completed']);
                })
                ->count();
        }

        $recorded = RecordedCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id)->where('status', SessionSubscriptionStatus::ACTIVE->value);
            })
            ->count();

        return [
            'interactive' => $interactive,
            'recorded' => $recorded,
            'total' => $interactive + $recorded,
        ];
    }

    /**
     * Calculate Quran-specific progress and stats.
     */
    protected function calculateQuranProgress(User $user, $academy): array
    {
        $cacheKey = "student:quran_progress:{$user->id}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $academy) {
            $subscriptions = QuranSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->get();

            $trialRequests = QuranTrialRequest::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->count();

            $activeSubscriptions = QuranSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('subscription_type', 'individual')
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->count();

            $circlesCount = QuranCircle::where('academy_id', $academy->id)
                ->whereHas('students', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->count();

            return [
                'progress' => $subscriptions->avg('progress_percentage') ?? 0,
                'pages' => $subscriptions->sum('verses_memorized') ?? 0,
                'trialRequests' => $trialRequests,
                'activeSubscriptions' => $activeSubscriptions,
                'circlesCount' => $circlesCount,
            ];
        });
    }

    /**
     * Clear all statistics caches for a student.
     */
    public function clearStudentStatsCache(int $userId, int $academyId): void
    {
        Cache::forget("student:stats:{$userId}");
        Cache::forget("student:attendance_rate:{$userId}:{$academyId}");
        Cache::forget("student:quran_progress:{$userId}:{$academyId}");
    }
}
