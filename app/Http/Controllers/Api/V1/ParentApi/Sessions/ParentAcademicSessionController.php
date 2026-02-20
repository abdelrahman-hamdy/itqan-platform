<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Sessions;

use App\Enums\SessionStatus;
use App\Http\Helpers\PaginationHelper;
use App\Models\AcademicSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Handles Academic session operations for parents.
 *
 * Allows parents to view their children's academic tutoring sessions.
 */
class ParentAcademicSessionController extends BaseParentSessionController
{
    /**
     * Get Academic sessions for parent's children.
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

            $query = AcademicSession::where('student_id', $studentUserId)
                ->with(['academicTeacher.user', 'academicSubscription']);

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

            $academicSessions = $query->orderBy('scheduled_at', 'desc')
                ->limit(50)
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort and paginate
        $sessions = $this->sortSessions($sessions);
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        return $this->success(
            $this->paginateSessions($sessions, $page, $perPage),
            __('Academic sessions retrieved successfully')
        );
    }

    /**
     * Get a specific Academic session.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $childUserIds = $this->getChildUserIds($parentProfile->id);

        $session = AcademicSession::where('id', $id)
            ->whereIn('student_id', $childUserIds)
            ->with(['academicTeacher.user', 'student', 'academicSubscription', 'reports'])
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Find which child this session belongs to
        $children = $this->getChildren($parentProfile->id);
        $student = $children->first(function ($rel) use ($session) {
            $studentUserId = $this->getStudentUserId($rel->student);

            return $session->student_id == $studentUserId;
        })?->student;

        return $this->success([
            'session' => $this->formatSessionDetail($session, $student),
        ], __('Session retrieved successfully'));
    }

    /**
     * Get today's Academic sessions.
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

            $academicSessions = AcademicSession::where('student_id', $studentUserId)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['academicTeacher.user', 'academicSubscription'])
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort by time (ascending for today)
        $sessions = $this->sortSessions($sessions, true);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
            'date' => $today->toDateString(),
        ], __('Today\'s Academic sessions retrieved successfully'));
    }

    /**
     * Get upcoming Academic sessions.
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

            $academicSessions = AcademicSession::where('student_id', $studentUserId)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['academicTeacher.user', 'academicSubscription'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($academicSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort and limit
        $sessions = $this->sortSessions($sessions, true);
        $sessions = array_slice($sessions, 0, $limit);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Upcoming Academic sessions retrieved successfully'));
    }

    /**
     * Format Academic session for list.
     *
     * @param  mixed  $student
     */
    protected function formatSession(AcademicSession $session, $student): array
    {
        $base = $this->formatBaseSession('academic', $session, $student);

        return array_merge($base, [
            'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
            'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
            'teacher_name' => $session->academicTeacher?->user?->name,
        ]);
    }

    /**
     * Format Academic session detail.
     *
     * @param  mixed  $student
     */
    protected function formatSessionDetail(AcademicSession $session, $student): array
    {
        $base = $this->formatSession($session, $student);

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
            'report' => ($report = $session->reports?->first()) ? [
                'rating' => $report->rating,
                'notes' => $report->notes,
                'teacher_feedback' => $report->teacher_feedback,
            ] : null,
            'meeting_link' => $session->meeting_link,
            'started_at' => $session->started_at?->toISOString(),
            'ended_at' => $session->ended_at?->toISOString(),
        ]);
    }
}
