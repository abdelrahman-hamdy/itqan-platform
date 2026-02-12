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
                'sessionHomework',
                'attendances' => function ($q) use ($user) {
                    $q->where('student_id', $user->id);
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

        // TODO: Add student_rating and student_feedback columns to quran_sessions table
        return $this->error(__('Feedback submission is not yet available.'), 501, 'NOT_IMPLEMENTED');
    }

    /**
     * Format session details for single view.
     */
    protected function formatSessionDetails($session): array
    {
        $base = $this->formatCommonSessionDetails($session, 'quran');

        // Quran-specific details
        $base['quran_details'] = [
            'session_mode' => $session->session_type,
            'lesson_content' => $session->lesson_content,
            'tajweed_rating' => null, // Column was removed from DB
            'memorization_rating' => null, // Column was removed from DB
            'homework_assigned' => $session->homework_assigned,
            'homework_details' => $session->homework_details,
            'current_verse' => $session->current_verse,
            'verses_covered_start' => $session->verses_covered_start,
            'verses_covered_end' => $session->verses_covered_end,
            'verses_memorized_today' => $session->verses_memorized_today,
        ];

        if ($session->sessionHomework) {
            $hw = $session->sessionHomework;
            $base['quran_details']['homework_data'] = [
                'has_new_memorization' => $hw->has_new_memorization,
                'new_memorization_surah' => $hw->new_memorization_surah,
                'new_memorization_from_verse' => $hw->new_memorization_from_verse,
                'new_memorization_to_verse' => $hw->new_memorization_to_verse,
                'new_memorization_pages' => $hw->new_memorization_pages,
                'has_review' => $hw->has_review,
                'review_surah' => $hw->review_surah,
                'review_from_verse' => $hw->review_from_verse,
                'review_to_verse' => $hw->review_to_verse,
                'review_pages' => $hw->review_pages,
                'additional_instructions' => $hw->additional_instructions,
                'due_date' => $hw->due_date?->toDateString(),
            ];
        }

        return $base;
    }
}
