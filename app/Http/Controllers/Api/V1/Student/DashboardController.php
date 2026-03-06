<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicHomework;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use App\Services\Unified\UnifiedSessionFetchingService;
use App\Services\Unified\UnifiedSubscriptionFetchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
     *
     * Counts QuizAssignment records for all educational entities the student is enrolled in
     * where the student has not yet submitted a completed attempt.
     * Uses the same assignable-entity resolution logic as QuizController::getStudentAssignableIds().
     *
     * QuizAttempt.student_id references StudentProfile.id (not User.id).
     */
    protected function getPendingQuizzesCount(int $userId): int
    {
        $user = \App\Models\User::find($userId);
        if (! $user) {
            return 0;
        }

        $studentProfile = $user->studentProfile()->first();
        if (! $studentProfile) {
            return 0;
        }

        $studentProfileId = $studentProfile->id;

        // Build the map of assignable_type → [ids] the student belongs to,
        // mirroring QuizController::getStudentAssignableIds().
        $assignableIds = [];

        // Quran subscriptions (education_unit_type / education_unit_id)
        // quran_subscriptions.student_id references User.id
        $quranSubs = $user->quranSubscriptions()
            ->whereNotNull('education_unit_type')
            ->whereNotNull('education_unit_id')
            ->select('education_unit_type', 'education_unit_id')
            ->get();

        foreach ($quranSubs as $sub) {
            $type = $sub->education_unit_type;
            $id = $sub->education_unit_id;
            if (! isset($assignableIds[$type])) {
                $assignableIds[$type] = [];
            }
            if (! in_array($id, $assignableIds[$type])) {
                $assignableIds[$type][] = $id;
            }
        }

        // Quran individual circles (student_id → User.id)
        $quranIndividualCircleIds = QuranIndividualCircle::where('student_id', $userId)
            ->pluck('id')
            ->toArray();
        if (! empty($quranIndividualCircleIds)) {
            $assignableIds['App\\Models\\QuranIndividualCircle'] = $quranIndividualCircleIds;
        }

        // Academic individual lessons (student_id → User.id)
        $academicLessonIds = AcademicIndividualLesson::where('student_id', $userId)
            ->pluck('id')
            ->toArray();
        if (! empty($academicLessonIds)) {
            $assignableIds['App\\Models\\AcademicIndividualLesson'] = $academicLessonIds;
        }

        // Interactive course enrollments (student_id → StudentProfile.id)
        $interactiveCourseIds = InteractiveCourseEnrollment::where('student_id', $studentProfileId)
            ->pluck('course_id')
            ->toArray();
        if (! empty($interactiveCourseIds)) {
            $assignableIds['App\\Models\\InteractiveCourse'] = $interactiveCourseIds;
        }

        // Recorded courses from course subscriptions (student_id → User.id)
        $recordedCourseIds = $user->courseSubscriptions()
            ->whereNotNull('recorded_course_id')
            ->pluck('recorded_course_id')
            ->toArray();
        if (! empty($recordedCourseIds)) {
            $assignableIds['App\\Models\\RecordedCourse'] = $recordedCourseIds;
        }

        if (empty($assignableIds)) {
            return 0;
        }

        // Count QuizAssignment records for those entities where the student has NOT
        // submitted a completed attempt (submitted_at IS NULL means in-progress or
        // no attempt at all — we exclude those with a submitted attempt).
        return QuizAssignment::where(function ($q) use ($assignableIds) {
            foreach ($assignableIds as $type => $ids) {
                $q->orWhere(function ($subQ) use ($type, $ids) {
                    $subQ->where('assignable_type', $type)
                        ->whereIn('assignable_id', $ids);
                });
            }
        })
            ->whereHas('quiz', function ($q) {
                $q->where('is_active', true);
            })
            ->whereDoesntHave('attempts', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId)
                    ->whereNotNull('submitted_at');
            })
            ->count();
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
