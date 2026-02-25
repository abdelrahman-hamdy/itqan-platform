<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AcademicSessionController extends BaseStudentSessionController
{
    /**
     * Get all Academic sessions for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get filter parameters
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = AcademicSession::where('student_id', $user->id)
            ->with(['academicTeacher.user', 'academicSubscription']);

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
            ->map(fn ($s) => $this->formatSession($s, 'academic'))
            ->toArray();

        $page = (int) $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->success(
            $this->manualPaginateSessions($sessions, $page, $perPage),
            __('Academic sessions retrieved successfully')
        );
    }

    /**
     * Get today's Academic sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();

        $sessions = AcademicSession::where('student_id', $user->id)
            ->whereDate('scheduled_at', $today)
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'academic'))
            ->toArray();

        return $this->success([
            'date' => $today->toDateString(),
            'sessions' => $sessions,
            'count' => count($sessions),
        ], __('Today\'s Academic sessions retrieved successfully'));
    }

    /**
     * Get upcoming Academic sessions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $endDate = $now->copy()->addDays(14);

        $sessions = AcademicSession::where('student_id', $user->id)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'academic'))
            ->toArray();

        return $this->success([
            'sessions' => $sessions,
            'from_date' => $now->toDateString(),
            'to_date' => $endDate->toDateString(),
        ], __('Upcoming Academic sessions retrieved successfully'));
    }

    /**
     * Get a specific Academic session.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->with([
                'academicTeacher.user',
                'academicSubscription.subject',
                'attendances' => function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                },
            ])
            ->first();

        if (! $session) {
            return $this->notFound(__('Academic session not found.'));
        }

        return $this->success([
            'session' => $this->formatSessionDetails($session),
        ], __('Academic session retrieved successfully'));
    }

    /**
     * Submit feedback for an Academic session.
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

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->first();

        if (! $session) {
            return $this->notFound(__('Academic session not found or not completed yet.'));
        }

        return $this->error(__('Feedback submission is not yet available.'), 501, 'NOT_IMPLEMENTED');
    }

    /**
     * Format session details for single view.
     */
    protected function formatSessionDetails($session): array
    {
        $base = $this->formatCommonSessionDetails($session, 'academic');

        // Academic-specific details
        $base['academic_details'] = [
            'subject' => $session->academicSubscription?->subject_name,
            'lesson_content' => $session->lesson_content,
            'homework' => $session->homework_description,
            'homework_assigned' => $session->homework_assigned,
            'homework_file' => $session->homework_file ? asset('storage/' . $session->homework_file) : null,
        ];

        return $base;
    }
}
