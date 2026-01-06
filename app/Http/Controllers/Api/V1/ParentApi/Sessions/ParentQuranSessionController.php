<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Sessions;

use App\Enums\SessionStatus;
use App\Http\Helpers\PaginationHelper;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Handles Quran session operations for parents.
 *
 * Allows parents to view their children's Quran sessions,
 * including individual and group circles.
 */
class ParentQuranSessionController extends BaseParentSessionController
{
    /**
     * Get Quran sessions for parent's children.
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

            $query = QuranSession::where('student_id', $studentUserId)
                ->with(['quranTeacher', 'individualCircle', 'circle']);

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

            $quranSessions = $query->orderBy('scheduled_at', 'desc')
                ->limit(50)
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort and paginate
        $sessions = $this->sortSessions($sessions);
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        return $this->success(
            $this->paginateSessions($sessions, $page, $perPage),
            __('Quran sessions retrieved successfully')
        );
    }

    /**
     * Get a specific Quran session.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        $childUserIds = $this->getChildUserIds($parentProfile->id);

        $session = QuranSession::where('id', $id)
            ->whereIn('student_id', $childUserIds)
            ->with(['quranTeacher', 'student.user', 'individualCircle', 'circle', 'reports'])
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
     * Get today's Quran sessions.
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

            $quranSessions = QuranSession::where('student_id', $studentUserId)
                ->whereDate('scheduled_at', $today)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                ->with(['quranTeacher'])
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort by time (ascending for today)
        $sessions = $this->sortSessions($sessions, true);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
            'date' => $today->toDateString(),
        ], __('Today\'s Quran sessions retrieved successfully'));
    }

    /**
     * Get upcoming Quran sessions.
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

            $quranSessions = QuranSession::where('student_id', $studentUserId)
                ->where('scheduled_at', '>', $now)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['quranTeacher'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($quranSessions as $session) {
                $sessions[] = $this->formatSession($session, $student);
            }
        }

        // Sort and limit
        $sessions = $this->sortSessions($sessions, true);
        $sessions = array_slice($sessions, 0, $limit);

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Upcoming Quran sessions retrieved successfully'));
    }

    /**
     * Format Quran session for list.
     *
     * @param  mixed  $student
     */
    protected function formatSession(QuranSession $session, $student): array
    {
        $base = $this->formatBaseSession('quran', $session, $student);

        return array_merge($base, [
            'title' => $session->title ?? 'جلسة قرآنية',
            'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
            'teacher_name' => $session->quranTeacher?->name,
        ]);
    }

    /**
     * Format Quran session detail.
     *
     * @param  mixed  $student
     */
    protected function formatSessionDetail(QuranSession $session, $student): array
    {
        $base = $this->formatSession($session, $student);

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
}
