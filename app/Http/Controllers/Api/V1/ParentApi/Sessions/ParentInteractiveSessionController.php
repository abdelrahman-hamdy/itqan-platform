<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Sessions;

use App\Enums\SessionStatus;
use App\Http\Helpers\PaginationHelper;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Handles Interactive Course session operations for parents.
 *
 * Allows parents to view their children's interactive course sessions.
 */
class ParentInteractiveSessionController extends BaseParentSessionController
{
    /**
     * Get Interactive sessions for parent's children.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $children = $this->getChildren($parentProfile->id, $request->get('child_id'));

        if ($children->isEmpty()) {
            return $this->success(['sessions' => [], 'pagination' => PaginationHelper::fromArray(0, 1, 15)], __('No sessions found.'));
        }

        $sessions = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            // Get enrolled courses
            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            if ($enrolledCourseIds->isEmpty()) {
                continue;
            }

            $query = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                ->with(['course.assignedTeacher.user']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('scheduled_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('scheduled_at', '<=', $request->to_date);
            }

            $interactiveSessions = $query->orderBy('scheduled_at', 'desc')
                ->limit(50)
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort and paginate
        $sessions = $this->sortSessions($sessions);
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        return $this->success(
            $this->paginateSessions($sessions, $page, $perPage),
            __('Interactive sessions retrieved successfully')
        );
    }

    /**
     * Get a specific Interactive session.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $childUserIds = $this->getChildUserIds($parentProfile->id);

        // Get enrolled courses for all children
        $enrolledCourseIds = CourseSubscription::whereIn('student_id', $childUserIds)
            ->pluck('interactive_course_id');

        $session = InteractiveCourseSession::where('id', $id)
            ->whereIn('course_id', $enrolledCourseIds)
            ->with(['course.assignedTeacher.user'])
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Find which child is enrolled in this course
        $children = $this->getChildren($parentProfile->id);
        $student = $children->first(function ($rel) use ($session) {
            $studentUserId = $this->getStudentUserId($rel->student);

            return CourseSubscription::where('student_id', $studentUserId)
                ->where('interactive_course_id', $session->course_id)
                ->exists();
        })?->student;

        return $this->success([
            'session' => $this->formatSessionDetail($session, $student),
        ], __('Session retrieved successfully'));
    }

    /**
     * Get today's Interactive sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $today = Carbon::today();
        $sessions = [];
        $children = $this->getChildren($parentProfile->id);

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            if ($enrolledCourseIds->isEmpty()) {
                continue;
            }

            $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['course.assignedTeacher.user'])
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort by time (ascending for today)
        $sessions = $this->sortSessions($sessions, true);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
            'date' => $today->toDateString(),
        ], __('Today\'s Interactive sessions retrieved successfully'));
    }

    /**
     * Get upcoming Interactive sessions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $now = now();
        $limit = $request->get('limit', 10);
        $sessions = [];
        $children = $this->getChildren($parentProfile->id);

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id');

            if ($enrolledCourseIds->isEmpty()) {
                continue;
            }

            $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['course.assignedTeacher.user'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort and limit
        $sessions = $this->sortSessions($sessions, true);
        $sessions = array_slice($sessions, 0, $limit);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Upcoming Interactive sessions retrieved successfully'));
    }

    /**
     * Format Interactive session for list.
     *
     * @param  mixed  $student
     */
    protected function formatSession(InteractiveCourseSession $session, $student): array
    {
        $base = $this->formatBaseSession('interactive', $session, $student);

        return array_merge($base, [
            'title' => $session->title ?? $session->course?->title,
            'course_name' => $session->course?->title,
            'teacher_name' => $session->course?->assignedTeacher?->user?->name,
            'session_number' => $session->session_number,
        ]);
    }

    /**
     * Format Interactive session detail.
     *
     * @param  mixed  $student
     */
    protected function formatSessionDetail(InteractiveCourseSession $session, $student): array
    {
        $base = $this->formatSession($session, $student);

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
            'meeting_url' => $session->meeting_link,
        ]);
    }
}
