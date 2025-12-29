<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Sessions;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Unified session controller for parents.
 *
 * Provides aggregated session views across all session types
 * for all children linked to the parent.
 */
class ParentUnifiedSessionController extends BaseParentSessionController
{
    /**
     * Get all sessions (Quran, Academic, Interactive) for parent's children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $children = $this->getChildren($parentProfile->id, $request->get('child_id'));

        if ($children->isEmpty()) {
            return $this->success(['sessions' => [], 'pagination' => PaginationHelper::fromArray(0, 1, 15)], __('No sessions found.'));
        }

        $sessions = [];
        $type = $request->get('type'); // quran, academic, interactive, or null for all

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            // Get Quran sessions
            if (!$type || $type === 'quran') {
                $sessions = array_merge(
                    $sessions,
                    $this->getQuranSessions($studentUserId, $student, $request)
                );
            }

            // Get Academic sessions
            if (!$type || $type === 'academic') {
                $sessions = array_merge(
                    $sessions,
                    $this->getAcademicSessions($studentUserId, $student, $request)
                );
            }

            // Get Interactive sessions
            if (!$type || $type === 'interactive') {
                $sessions = array_merge(
                    $sessions,
                    $this->getInteractiveSessions($studentUserId, $student, $request)
                );
            }
        }

        // Sort and paginate
        $sessions = $this->sortSessions($sessions);
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        return $this->success(
            $this->paginateSessions($sessions, $page, $perPage),
            __('Sessions retrieved successfully')
        );
    }

    /**
     * Get a specific session by type and ID.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $childUserIds = $this->getChildUserIds($parentProfile->id);
        $children = $this->getChildren($parentProfile->id);

        $session = match ($type) {
            'quran' => QuranSession::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->with(['quranTeacher', 'student.user', 'individualCircle', 'circle', 'reports'])
                ->first(),
            'academic' => AcademicSession::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->with(['academicTeacher.user', 'student.user', 'academicSubscription', 'reports'])
                ->first(),
            'interactive' => $this->getInteractiveSession($id, $childUserIds),
            default => null,
        };

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        // Find which child this session belongs to
        $student = $this->findSessionStudent($session, $type, $children);

        return $this->success([
            'session' => $this->formatSessionDetail($type, $session, $student),
        ], __('Session retrieved successfully'));
    }

    /**
     * Get today's sessions for all children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $today = Carbon::today();
        $sessions = [];
        $children = $this->getChildren($parentProfile->id);

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            // Quran sessions
            $quranSessions = QuranSession::where('student_id', $studentUserId)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['quranTeacher'])
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSessionSimple('quran', $session, $student);
            }

            // Academic sessions
            $academicSessions = AcademicSession::where('student_id', $studentUserId)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['academicTeacher.user', 'academicSubscription'])
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = $this->formatSessionSimple('academic', $session, $student);
            }

            // Interactive sessions
            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            if ($enrolledCourseIds->isNotEmpty()) {
                $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                    ->whereDate('scheduled_at', $today)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['course.assignedTeacher.user'])
                    ->get();

                foreach ($interactiveSessions as $session) {
                    $sessions[] = $this->formatSessionSimple('interactive', $session, $student);
                }
            }
        }

        // Sort by time (ascending for today)
        $sessions = $this->sortSessions($sessions, true);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
            'date' => $today->toDateString(),
        ], __('Today\'s sessions retrieved successfully'));
    }

    /**
     * Get upcoming sessions for all children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $now = now();
        $limit = $request->get('limit', 10);
        $sessions = [];
        $children = $this->getChildren($parentProfile->id);

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            // Quran sessions
            $quranSessions = QuranSession::where('student_id', $studentUserId)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['quranTeacher'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSessionSimple('quran', $session, $student);
            }

            // Academic sessions
            $academicSessions = AcademicSession::where('student_id', $studentUserId)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['academicTeacher.user', 'academicSubscription'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = $this->formatSessionSimple('academic', $session, $student);
            }

            // Interactive sessions
            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            if ($enrolledCourseIds->isNotEmpty()) {
                $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                    ->where('scheduled_at', '>', $now)
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                    ->with(['course.assignedTeacher.user'])
                    ->orderBy('scheduled_at')
                    ->limit($limit)
                    ->get();

                foreach ($interactiveSessions as $session) {
                    $sessions[] = $this->formatSessionSimple('interactive', $session, $student);
                }
            }
        }

        // Sort and limit
        $sessions = $this->sortSessions($sessions, true);
        $sessions = array_slice($sessions, 0, $limit);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Upcoming sessions retrieved successfully'));
    }

    /**
     * Get Quran sessions for a student.
     */
    protected function getQuranSessions(int $studentUserId, $student, Request $request): array
    {
        $query = QuranSession::where('student_id', $studentUserId)
            ->with(['quranTeacher', 'individualCircle', 'circle']);

        $this->applyFilters($query, $request);

        return $query->orderBy('scheduled_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($s) => $this->formatSessionSimple('quran', $s, $student))
            ->toArray();
    }

    /**
     * Get Academic sessions for a student.
     */
    protected function getAcademicSessions(int $studentUserId, $student, Request $request): array
    {
        $query = AcademicSession::where('student_id', $studentUserId)
            ->with(['academicTeacher.user', 'academicSubscription']);

        $this->applyFilters($query, $request);

        return $query->orderBy('scheduled_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($s) => $this->formatSessionSimple('academic', $s, $student))
            ->toArray();
    }

    /**
     * Get Interactive sessions for a student.
     */
    protected function getInteractiveSessions(int $studentUserId, $student, Request $request): array
    {
        $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
            ->pluck('interactive_course_id');

        if ($enrolledCourseIds->isEmpty()) {
            return [];
        }

        $query = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
            ->with(['course.assignedTeacher.user']);

        $this->applyFilters($query, $request);

        return $query->orderBy('scheduled_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($s) => $this->formatSessionSimple('interactive', $s, $student))
            ->toArray();
    }

    /**
     * Apply common filters to session query.
     */
    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('scheduled_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('scheduled_at', '<=', $request->to_date);
        }
    }

    /**
     * Get interactive session with enrollment check.
     */
    protected function getInteractiveSession(int $id, array $childUserIds)
    {
        $enrolledCourseIds = CourseSubscription::whereIn('student_id', $childUserIds)
            ->pluck('interactive_course_id');

        return InteractiveCourseSession::where('id', $id)
            ->whereIn('course_id', $enrolledCourseIds)
            ->with(['course.assignedTeacher.user'])
            ->first();
    }

    /**
     * Find which student owns a session.
     */
    protected function findSessionStudent($session, string $type, $children)
    {
        foreach ($children as $rel) {
            $studentUserId = $this->getStudentUserId($rel->student);

            if ($type === 'interactive') {
                $enrolled = CourseSubscription::where('student_id', $studentUserId)
                    ->where('interactive_course_id', $session->course_id)
                    ->exists();
                if ($enrolled) {
                    return $rel->student;
                }
            } else {
                if ($session->student_id == $studentUserId) {
                    return $rel->student;
                }
            }
        }

        return null;
    }

    /**
     * Format session for simple list (used in index/today/upcoming).
     */
    protected function formatSessionSimple(string $type, $session, $student): array
    {
        $base = $this->formatBaseSession($type, $session, $student);

        return match ($type) {
            'quran' => array_merge($base, [
                'title' => $session->title ?? 'جلسة قرآنية',
                'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                'teacher_name' => $session->quranTeacher?->name,
            ]),
            'academic' => array_merge($base, [
                'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
                'teacher_name' => $session->academicTeacher?->user?->name,
            ]),
            'interactive' => array_merge($base, [
                'title' => $session->title ?? $session->course?->title,
                'course_name' => $session->course?->title,
                'teacher_name' => $session->course?->assignedTeacher?->user?->name,
                'session_number' => $session->session_number,
            ]),
        };
    }

    /**
     * Format session detail (used in show).
     */
    protected function formatSessionDetail(string $type, $session, $student): array
    {
        $base = $this->formatSessionSimple($type, $session, $student);

        if ($type === 'quran') {
            return array_merge($base, [
                'teacher' => $session->quranTeacher ? [
                    'id' => $session->quranTeacher->id,
                    'name' => $session->quranTeacher->name,
                    'avatar' => $session->quranTeacher->avatar
                        ? asset('storage/' . $session->quranTeacher->avatar)
                        : null,
                ] : null,
                'circle' => [
                    'id' => $session->individualCircle?->id ?? $session->circle?->id,
                    'name' => $session->individualCircle?->name ?? $session->circle?->name,
                    'type' => $session->circle_id ? 'group' : 'individual',
                ],
                'homework' => [
                    'memorization' => $session->quran_homework_memorization,
                    'recitation' => $session->quran_homework_recitation,
                    'review' => $session->quran_homework_review,
                ],
                'progress' => [
                    'current_surah' => $session->current_surah,
                    'current_page' => $session->current_page,
                ],
                'evaluation' => $session->reports?->first() ? [
                    'memorization_degree' => $session->reports->first()->new_memorization_degree,
                    'revision_degree' => $session->reports->first()->reservation_degree,
                    'overall_performance' => $session->reports->first()->overall_performance,
                    'notes' => $session->reports->first()->notes,
                    'evaluated_at' => $session->reports->first()->evaluated_at?->toISOString(),
                ] : null,
                'meeting_link' => $session->meeting_link,
                'started_at' => $session->started_at?->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
            ]);
        }

        if ($type === 'academic') {
            return array_merge($base, [
                'teacher' => $session->academicTeacher?->user ? [
                    'id' => $session->academicTeacher->user->id,
                    'name' => $session->academicTeacher->user->name,
                    'avatar' => $session->academicTeacher->user->avatar
                        ? asset('storage/' . $session->academicTeacher->user->avatar)
                        : null,
                ] : null,
                'subscription' => $session->academicSubscription ? [
                    'id' => $session->academicSubscription->id,
                    'subject' => $session->academicSubscription->subject?->name ?? $session->academicSubscription->subject_name,
                ] : null,
                'homework' => $session->homework,
                'lesson_content' => $session->lesson_content,
                'topics_covered' => $session->topics_covered ?? [],
                'report' => $session->reports?->first() ? [
                    'rating' => $session->reports->first()->rating,
                    'notes' => $session->reports->first()->notes,
                    'teacher_feedback' => $session->reports->first()->teacher_feedback,
                ] : null,
                'meeting_link' => $session->meeting_link,
                'started_at' => $session->started_at?->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
            ]);
        }

        // Interactive
        return array_merge($base, [
            'teacher' => $session->course?->assignedTeacher?->user ? [
                'id' => $session->course->assignedTeacher->user->id,
                'name' => $session->course->assignedTeacher->user->name,
                'avatar' => $session->course->assignedTeacher->user->avatar
                    ? asset('storage/' . $session->course->assignedTeacher->user->avatar)
                    : null,
            ] : null,
            'course' => $session->course ? [
                'id' => $session->course->id,
                'title' => $session->course->title,
                'thumbnail' => $session->course->thumbnail
                    ? asset('storage/' . $session->course->thumbnail)
                    : null,
            ] : null,
            'description' => $session->description,
            'materials' => $session->materials ?? [],
            'meeting_link' => $session->meeting_link,
        ]);
    }
}
