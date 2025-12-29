<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\PaginatesResults;
use App\Services\Session\StudentSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    use ApiResponses, PaginatesResults;

    public function __construct(
        private StudentSessionService $sessionService
    ) {}

    /**
     * Get all sessions for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get filter parameters
        $type = $request->get('type');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $sessions = $this->sessionService->getStudentSessions(
            $user->id,
            $type,
            $status,
            $dateFrom,
            $dateTo
        );

        // Manual pagination
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $total = count($sessions);
        $offset = ($page - 1) * $perPage;
        $paginatedSessions = array_slice($sessions, $offset, $perPage);

        return $this->success([
            'sessions' => $paginatedSessions,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get today's sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = $this->sessionService->getTodaySessions($user->id);

        return $this->success([
            'date' => now()->toDateString(),
            'sessions' => $sessions,
            'count' => count($sessions),
        ], __('Today\'s sessions retrieved successfully'));
    }

    /**
     * Get upcoming sessions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $endDate = $now->copy()->addDays(14);

        $sessions = $this->sessionService->getUpcomingSessions($user->id, 14, 20);

        return $this->success([
            'sessions' => $sessions,
            'from_date' => $now->toDateString(),
            'to_date' => $endDate->toDateString(),
        ], __('Upcoming sessions retrieved successfully'));
    }

    /**
     * Get a specific session.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $session = $this->sessionService->getSessionDetail($user->id, $type, $id);

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'session' => $session,
        ], __('Session retrieved successfully'));
    }

    /**
     * Submit feedback for a session.
     */
    public function submitFeedback(Request $request, string $type, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $success = $this->sessionService->submitFeedback(
            $user->id,
            $type,
            $id,
            $request->rating,
            $request->feedback
        );

        if (!$success) {
            return $this->error(
                __('Session not found, not completed, or feedback already submitted.'),
                400,
                'FEEDBACK_SUBMISSION_FAILED'
            );
        }

        return $this->success([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ], __('Feedback submitted successfully'));
    }
}
