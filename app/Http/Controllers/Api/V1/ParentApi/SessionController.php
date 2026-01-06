<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SessionController extends Controller
{
    use ApiResponses;

    /**
     * Get all sessions for linked children.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        $sessions = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Filter by specific child if requested
            if ($request->filled('child_id') && $student->id != $request->child_id) {
                continue;
            }

            // Get Quran sessions
            if (! $request->filled('type') || $request->type === 'quran') {
                $quranQuery = QuranSession::where('student_id', $studentUserId)
                    ->with(['quranTeacher', 'individualCircle', 'circle']);

                if ($request->filled('status')) {
                    $quranQuery->where('status', $request->status);
                }

                if ($request->filled('from_date')) {
                    $quranQuery->whereDate('scheduled_at', '>=', $request->from_date);
                }

                if ($request->filled('to_date')) {
                    $quranQuery->whereDate('scheduled_at', '<=', $request->to_date);
                }

                $quranSessions = $quranQuery->orderBy('scheduled_at', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($quranSessions as $session) {
                    $sessions[] = $this->formatSession('quran', $session, $student);
                }
            }

            // Get Academic sessions
            if (! $request->filled('type') || $request->type === 'academic') {
                $academicQuery = AcademicSession::where('student_id', $studentUserId)
                    ->with(['academicTeacher.user', 'academicSubscription']);

                if ($request->filled('status')) {
                    $academicQuery->where('status', $request->status);
                }

                if ($request->filled('from_date')) {
                    $academicQuery->whereDate('scheduled_at', '>=', $request->from_date);
                }

                if ($request->filled('to_date')) {
                    $academicQuery->whereDate('scheduled_at', '<=', $request->to_date);
                }

                $academicSessions = $academicQuery->orderBy('scheduled_at', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($academicSessions as $session) {
                    $sessions[] = $this->formatSession('academic', $session, $student);
                }
            }

            // Get Interactive course sessions
            if (! $request->filled('type') || $request->type === 'interactive') {
                $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                    ->pluck('interactive_course_id');

                $interactiveQuery = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                    ->with(['course.assignedTeacher.user']);

                if ($request->filled('status')) {
                    $interactiveQuery->where('status', $request->status);
                }

                if ($request->filled('from_date')) {
                    $interactiveQuery->whereDate('scheduled_at', '>=', $request->from_date);
                }

                if ($request->filled('to_date')) {
                    $interactiveQuery->whereDate('scheduled_at', '<=', $request->to_date);
                }

                $interactiveSessions = $interactiveQuery->orderBy('scheduled_at', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($interactiveSessions as $session) {
                    $sessions[] = $this->formatSession('interactive', $session, $student);
                }
            }
        }

        // Sort all sessions by scheduled time
        usort($sessions, function ($a, $b) {
            return strtotime($b['scheduled_at']) <=> strtotime($a['scheduled_at']);
        });

        // Paginate manually
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($sessions);
        $sessions = array_slice($sessions, ($page - 1) * $perPage, $perPage);

        return $this->success([
            'sessions' => $sessions,
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get a specific session.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children's user IDs
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        $childUserIds = $children->map(fn ($r) => $r->student->user?->id ?? $r->student->id)
            ->filter()
            ->toArray();

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

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Find which child this session belongs to
        $student = null;
        foreach ($children as $rel) {
            $studentUserId = $rel->student->user?->id ?? $rel->student->id;
            if ($type === 'quran' && $session->student_id == $studentUserId) {
                $student = $rel->student;
                break;
            }
            if ($type === 'academic' && $session->student_id == $studentUserId) {
                $student = $rel->student;
                break;
            }
            if ($type === 'interactive') {
                // For interactive, check if enrolled
                $enrolled = CourseSubscription::where('student_id', $studentUserId)
                    ->where('interactive_course_id', $session->course_id)
                    ->exists();
                if ($enrolled) {
                    $student = $rel->student;
                    break;
                }
            }
        }

        return $this->success([
            'session' => $this->formatSessionDetail($type, $session, $student),
        ], __('Session retrieved successfully'));
    }

    /**
     * Get today's sessions for all children.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $today = Carbon::today();
        $sessions = [];

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Quran sessions
            $quranSessions = QuranSession::where('student_id', $studentUserId)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['quranTeacher'])
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSession('quran', $session, $student);
            }

            // Academic sessions
            $academicSessions = AcademicSession::where('student_id', $studentUserId)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['academicTeacher.user', 'academicSubscription'])
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = $this->formatSession('academic', $session, $student);
            }

            // Interactive sessions
            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['course.assignedTeacher.user'])
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession('interactive', $session, $student);
            }
        }

        // Sort by time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
            'date' => $today->toDateString(),
        ], __('Today\'s sessions retrieved successfully'));
    }

    /**
     * Get upcoming sessions for all children.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $now = now();
        $limit = $request->get('limit', 10);
        $sessions = [];

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Quran sessions
            $quranSessions = QuranSession::where('student_id', $studentUserId)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['quranTeacher'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSession('quran', $session, $student);
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
                $sessions[] = $this->formatSession('academic', $session, $student);
            }

            // Interactive sessions
            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['course.assignedTeacher.user'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession('interactive', $session, $student);
            }
        }

        // Sort by time and limit
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        $sessions = array_slice($sessions, 0, $limit);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Upcoming sessions retrieved successfully'));
    }

    /**
     * Format session for list.
     */
    protected function formatSession(string $type, $session, $student): array
    {
        $base = [
            'id' => $session->id,
            'type' => $type,
            'child_id' => $student->id,
            'child_name' => $student->full_name,
            'status' => is_object($session->status) ? $session->status->value : $session->status,
        ];

        if ($type === 'quran') {
            return array_merge($base, [
                'title' => $session->title ?? 'جلسة قرآنية',
                'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                'teacher_name' => $session->quranTeacher?->name,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
            ]);
        }

        if ($type === 'academic') {
            return array_merge($base, [
                'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
                'teacher_name' => $session->academicTeacher?->user?->name,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
            ]);
        }

        // Interactive - all session types now use scheduled_at
        return array_merge($base, [
            'title' => $session->title ?? $session->course?->title,
            'course_name' => $session->course?->title,
            'teacher_name' => $session->course?->assignedTeacher?->user?->name,
            'session_number' => $session->session_number,
            'scheduled_at' => $session->scheduled_at?->toISOString(),
            'duration_minutes' => $session->duration_minutes ?? 60,
        ]);
    }

    /**
     * Format session detail.
     */
    protected function formatSessionDetail(string $type, $session, $student): array
    {
        $base = $this->formatSession($type, $session, $student);

        if ($type === 'quran') {
            return array_merge($base, [
                'teacher' => $session->quranTeacher ? [
                    'id' => $session->quranTeacher->id,
                    'name' => $session->quranTeacher->name,
                    'avatar' => $session->quranTeacher->avatar
                        ? asset('storage/'.$session->quranTeacher->avatar)
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
                'progress' => [],
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
                        ? asset('storage/'.$session->academicTeacher->user->avatar)
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
                    ? asset('storage/'.$session->course->assignedTeacher->user->avatar)
                    : null,
            ] : null,
            'course' => $session->course ? [
                'id' => $session->course->id,
                'title' => $session->course->title,
                'thumbnail' => $session->course->thumbnail
                    ? asset('storage/'.$session->course->thumbnail)
                    : null,
            ] : null,
            'description' => $session->description,
            'materials' => $session->materials ?? [],
            'meeting_link' => $session->meeting_link,
        ]);
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
}
