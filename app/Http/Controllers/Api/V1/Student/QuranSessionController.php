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

        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);

        return $this->success(
            $this->manualPaginateSessions($sessions, $page, $perPage),
            __('Quran sessions retrieved successfully')
        );
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
        $base = $this->formatCommonSessionDetails($session, 'quran');

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

        return $base;
    }
}
