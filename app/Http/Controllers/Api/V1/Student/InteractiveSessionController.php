<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\SessionStatus;
use App\Models\InteractiveCourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class InteractiveSessionController extends BaseStudentSessionController
{
    /**
     * Get all Interactive sessions for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return $this->success(
                $this->manualPaginateSessions([], 1, 15),
                __('Interactive sessions retrieved successfully')
            );
        }

        // Get filter parameters
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
            $q->where('student_id', $studentProfileId);
        })->with(['course.assignedTeacher.user']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        $sessions = $query->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'interactive'))
            ->toArray();

        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);

        return $this->success(
            $this->manualPaginateSessions($sessions, $page, $perPage),
            __('Interactive sessions retrieved successfully')
        );
    }

    /**
     * Get today's Interactive sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfileId = $user->studentProfile?->id;
        $today = Carbon::today();

        if (! $studentProfileId) {
            return $this->success([
                'date' => $today->toDateString(),
                'sessions' => [],
                'count' => 0,
            ], __('Today\'s Interactive sessions retrieved successfully'));
        }

        $sessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
            $q->where('student_id', $studentProfileId);
        })
            ->whereDate('scheduled_at', $today)
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'interactive'))
            ->toArray();

        return $this->success([
            'date' => $today->toDateString(),
            'sessions' => $sessions,
            'count' => count($sessions),
        ], __('Today\'s Interactive sessions retrieved successfully'));
    }

    /**
     * Get upcoming Interactive sessions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfileId = $user->studentProfile?->id;
        $now = now();
        $endDate = $now->copy()->addDays(14);

        if (! $studentProfileId) {
            return $this->success([
                'sessions' => [],
                'from_date' => $now->toDateString(),
                'to_date' => $endDate->toDateString(),
            ], __('Upcoming Interactive sessions retrieved successfully'));
        }

        $sessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
            $q->where('student_id', $studentProfileId);
        })
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'interactive'))
            ->toArray();

        return $this->success([
            'sessions' => $sessions,
            'from_date' => $now->toDateString(),
            'to_date' => $endDate->toDateString(),
        ], __('Upcoming Interactive sessions retrieved successfully'));
    }

    /**
     * Get a specific Interactive session.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return $this->notFound(__('Interactive session not found.'));
        }

        $session = InteractiveCourseSession::where('id', $id)
            ->whereHas('course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
            ->with([
                'course.assignedTeacher.user',
                'course.enrollments',
            ])
            ->first();

        if (! $session) {
            return $this->notFound(__('Interactive session not found.'));
        }

        return $this->success([
            'session' => $this->formatSessionDetails($session),
        ], __('Interactive session retrieved successfully'));
    }

    /**
     * Submit feedback for an Interactive session.
     */
    public function submitFeedback(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return $this->notFound(__('Interactive session not found or not completed yet.'));
        }

        $session = InteractiveCourseSession::where('id', $id)
            ->whereHas('course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
            ->where('status', SessionStatus::COMPLETED->value)
            ->first();

        if (! $session) {
            return $this->notFound(__('Interactive session not found or not completed yet.'));
        }

        // TODO: Add student_rating and student_feedback columns to interactive_course_sessions table
        return $this->error(__('Feedback submission is not yet available.'), 501, 'NOT_IMPLEMENTED');
    }

    /**
     * Format session details for single view.
     */
    protected function formatSessionDetails($session): array
    {
        $base = $this->formatCommonSessionDetails($session, 'interactive');

        // Interactive course-specific details
        $base['interactive_details'] = [
            'course_id' => $session->course_id,
            'course_name' => $session->course?->title,
            'lesson_number' => $session->session_number,
            'total_lessons' => $session->course?->total_sessions,
            'enrolled_count' => $session->course?->enrollments?->count() ?? 0,
            'max_capacity' => $session->course?->max_students,
        ];

        return $base;
    }
}
