<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class QuranSessionController extends BaseStudentSessionController
{
    /**
     * Get all Quran sessions for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get filter parameters
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = QuranSession::where('student_id', $user->id)
            ->with(['quranTeacher']);

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
            ->map(fn ($s) => $this->formatSession($s, 'quran'))
            ->toArray();

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
        ], __('Quran sessions retrieved successfully'));
    }

    /**
     * Get today's Quran sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();

        $sessions = QuranSession::where('student_id', $user->id)
            ->whereDate('scheduled_at', $today)
            ->with(['quranTeacher', 'individualCircle', 'circle'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'quran'))
            ->toArray();

        return $this->success([
            'date' => $today->toDateString(),
            'sessions' => $sessions,
            'count' => count($sessions),
        ], __('Today\'s Quran sessions retrieved successfully'));
    }

    /**
     * Get upcoming Quran sessions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $endDate = $now->copy()->addDays(14);

        $sessions = QuranSession::where('student_id', $user->id)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'quran'))
            ->toArray();

        return $this->success([
            'sessions' => $sessions,
            'from_date' => $now->toDateString(),
            'to_date' => $endDate->toDateString(),
        ], __('Upcoming Quran sessions retrieved successfully'));
    }

    /**
     * Get a specific Quran session.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $session = QuranSession::where('id', $id)
            ->where('student_id', $user->id)
            ->with([
                'quranTeacher',
                'individualCircle',
                'circle',
                'meeting',
                'attendances' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
            ->first();

        if (! $session) {
            return $this->notFound(__('Quran session not found.'));
        }

        return $this->success([
            'session' => $this->formatSessionDetails($session),
        ], __('Quran session retrieved successfully'));
    }

    /**
     * Submit feedback for a Quran session.
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

        $session = QuranSession::where('id', $id)
            ->where('student_id', $user->id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->first();

        if (! $session) {
            return $this->notFound(__('Quran session not found or not completed yet.'));
        }

        // Check if already submitted feedback
        if ($session->student_rating) {
            return $this->error(
                __('Feedback already submitted for this session.'),
                400,
                'FEEDBACK_ALREADY_SUBMITTED'
            );
        }

        $session->update([
            'student_rating' => $request->rating,
            'student_feedback' => $request->feedback,
        ]);

        return $this->success([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ], __('Feedback submitted successfully'));
    }

    /**
     * Format session details for single view.
     */
    protected function formatSessionDetails($session): array
    {
        $base = $this->formatSession($session, 'quran');

        // Add more details
        $base['description'] = $session->description;
        $base['notes'] = $session->notes ?? $session->teacher_notes ?? null;
        $base['student_rating'] = $session->student_rating;
        $base['student_feedback'] = $session->student_feedback;

        // Meeting info
        if ($session->meeting) {
            $base['meeting'] = [
                'id' => $session->meeting->id,
                'room_name' => $session->meeting->room_name,
                'status' => $session->meeting->status,
            ];
        }

        // Quran-specific details
        $base['quran_details'] = [
            'from_surah' => $session->from_surah,
            'from_verse' => $session->from_verse,
            'to_surah' => $session->to_surah,
            'to_verse' => $session->to_verse,
            'pages_count' => $session->pages_count,
            'memorization_quality' => $session->memorization_quality,
            'tajweed_quality' => $session->tajweed_quality,
        ];

        // Attendance info
        if (isset($session->attendances) && $session->attendances->isNotEmpty()) {
            $attendance = $session->attendances->first();
            $base['attendance'] = [
                'status' => $attendance->status,
                'attended_at' => $attendance->attended_at?->toISOString(),
                'left_at' => $attendance->left_at?->toISOString(),
                'duration_minutes' => $attendance->duration_minutes,
            ];
        }

        return $base;
    }
}
