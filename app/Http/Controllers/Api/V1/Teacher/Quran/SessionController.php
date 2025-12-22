<?php

namespace App\Http\Controllers\Api\V1\Teacher\Quran;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    use ApiResponses;

    /**
     * Get Quran sessions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = QuranSession::where('quran_teacher_id', $quranTeacherId)
            ->with(['student.user', 'individualCircle', 'circle']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by circle type
        if ($request->filled('circle_type')) {
            if ($request->circle_type === 'individual') {
                $query->whereNotNull('individual_circle_id');
            } else {
                $query->whereNotNull('circle_id');
            }
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('scheduled_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('scheduled_at', '<=', $request->to_date);
        }

        $sessions = $query->orderBy('scheduled_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'sessions' => collect($sessions->items())->map(fn($session) => [
                'id' => $session->id,
                'title' => $session->title ?? 'جلسة قرآنية',
                'student_name' => $session->student?->user?->name ?? $session->student?->full_name,
                'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                'circle_type' => $session->circle_id ? 'group' : 'individual',
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_link' => $session->meeting_link,
            ])->toArray(),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'total_pages' => $sessions->lastPage(),
                'has_more' => $sessions->hasMorePages(),
            ],
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get session detail.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['student.user', 'individualCircle', 'circle', 'reports', 'subscription'])
            ->first();

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'session' => [
                'id' => $session->id,
                'title' => $session->title ?? 'جلسة قرآنية',
                'student' => $session->student?->user ? [
                    'id' => $session->student->user->id,
                    'name' => $session->student->user->name,
                    'avatar' => $session->student->user->avatar
                        ? asset('storage/' . $session->student->user->avatar)
                        : null,
                    'phone' => $session->student?->phone ?? $session->student->user->phone,
                ] : null,
                'circle' => [
                    'id' => $session->individualCircle?->id ?? $session->circle?->id,
                    'name' => $session->individualCircle?->name ?? $session->circle?->name,
                    'type' => $session->circle_id ? 'group' : 'individual',
                ],
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_link' => $session->meeting_link,
                'homework' => [
                    'memorization' => $session->quran_homework_memorization,
                    'recitation' => $session->quran_homework_recitation,
                    'review' => $session->quran_homework_review,
                ],
                'evaluation' => [
                    'memorization_rating' => $session->memorization_rating,
                    'tajweed_rating' => $session->tajweed_rating,
                    'current_surah' => $session->current_surah,
                    'current_page' => $session->current_page,
                    'verses_memorized' => $session->verses_memorized,
                    'pages_reviewed' => $session->pages_reviewed,
                ],
                'notes' => $session->notes,
                'teacher_notes' => $session->teacher_notes,
                'report' => $session->reports?->first() ? [
                    'id' => $session->reports->first()->id,
                    'rating' => $session->reports->first()->rating,
                    'notes' => $session->reports->first()->notes,
                    'teacher_feedback' => $session->reports->first()->teacher_feedback,
                ] : null,
                'started_at' => $session->started_at?->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
                'created_at' => $session->created_at->toISOString(),
            ],
        ], __('Session retrieved successfully'));
    }

    /**
     * Complete a session.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === 'completed') {
            return $this->error(__('Session is already completed.'), 400, 'ALREADY_COMPLETED');
        }

        if ($statusValue === 'cancelled') {
            return $this->error(__('Cannot complete a cancelled session.'), 400, 'SESSION_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'memorization_rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'tajweed_rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'current_surah' => ['sometimes', 'string', 'max:100'],
            'current_page' => ['sometimes', 'integer', 'min:1', 'max:604'],
            'verses_memorized' => ['sometimes', 'integer', 'min:0'],
            'pages_reviewed' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'homework_memorization' => ['sometimes', 'nullable', 'string', 'max:500'],
            'homework_recitation' => ['sometimes', 'nullable', 'string', 'max:500'],
            'homework_review' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $session->update([
                'status' => 'completed',
                'ended_at' => now(),
                'memorization_rating' => $request->memorization_rating ?? $session->memorization_rating,
                'tajweed_rating' => $request->tajweed_rating ?? $session->tajweed_rating,
                'current_surah' => $request->current_surah ?? $session->current_surah,
                'current_page' => $request->current_page ?? $session->current_page,
                'verses_memorized' => $request->verses_memorized ?? $session->verses_memorized,
                'pages_reviewed' => $request->pages_reviewed ?? $session->pages_reviewed,
                'notes' => $request->notes ?? $session->notes,
                'quran_homework_memorization' => $request->homework_memorization ?? $session->quran_homework_memorization,
                'quran_homework_recitation' => $request->homework_recitation ?? $session->quran_homework_recitation,
                'quran_homework_review' => $request->homework_review ?? $session->quran_homework_review,
            ]);

            // Update subscription usage
            if (method_exists($session, 'updateSubscriptionUsage')) {
                $session->updateSubscriptionUsage();
            }

            DB::commit();

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'status' => 'completed',
                    'ended_at' => $session->ended_at->toISOString(),
                ],
            ], __('Session completed successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('Failed to complete session.'), 500, 'COMPLETE_FAILED');
        }
    }

    /**
     * Cancel a session.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === 'completed') {
            return $this->error(__('Cannot cancel a completed session.'), 400, 'SESSION_COMPLETED');
        }

        if ($statusValue === 'cancelled') {
            return $this->error(__('Session is already cancelled.'), 400, 'ALREADY_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason,
            ],
        ], __('Session cancelled successfully'));
    }

    /**
     * Submit session evaluation.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function evaluate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'memorization_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'tajweed_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'current_surah' => ['sometimes', 'string', 'max:100'],
            'current_page' => ['sometimes', 'integer', 'min:1', 'max:604'],
            'verses_memorized' => ['sometimes', 'integer', 'min:0'],
            'pages_reviewed' => ['sometimes', 'integer', 'min:0'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'homework_memorization' => ['sometimes', 'nullable', 'string', 'max:500'],
            'homework_recitation' => ['sometimes', 'nullable', 'string', 'max:500'],
            'homework_review' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'memorization_rating' => $request->memorization_rating,
            'tajweed_rating' => $request->tajweed_rating,
            'current_surah' => $request->current_surah ?? $session->current_surah,
            'current_page' => $request->current_page ?? $session->current_page,
            'verses_memorized' => $request->verses_memorized ?? $session->verses_memorized,
            'pages_reviewed' => $request->pages_reviewed ?? $session->pages_reviewed,
            'teacher_notes' => $request->feedback ?? $session->teacher_notes,
            'quran_homework_memorization' => $request->homework_memorization ?? $session->quran_homework_memorization,
            'quran_homework_recitation' => $request->homework_recitation ?? $session->quran_homework_recitation,
            'quran_homework_review' => $request->homework_review ?? $session->quran_homework_review,
        ]);

        // TODO: DEFERRED - QuranSessionReport model doesn't exist
        // See DEFERRED_PROBLEMS.md for details
        // QuranSessionReport::updateOrCreate(
        //     ['quran_session_id' => $session->id],
        //     [
        //         'teacher_feedback' => $request->feedback,
        //         'memorization_rating' => $request->memorization_rating,
        //         'tajweed_rating' => $request->tajweed_rating,
        //         'rating' => round(($request->memorization_rating + $request->tajweed_rating) / 2),
        //     ]
        // );

        return $this->success([
            'session' => [
                'id' => $session->id,
                'memorization_rating' => $session->memorization_rating,
                'tajweed_rating' => $session->tajweed_rating,
            ],
        ], __('Evaluation submitted successfully'));
    }

    /**
     * Update session notes.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'teacher_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'notes' => $request->notes ?? $session->notes,
            'teacher_notes' => $request->teacher_notes ?? $session->teacher_notes,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'notes' => $session->notes,
                'teacher_notes' => $session->teacher_notes,
            ],
        ], __('Notes updated successfully'));
    }
}
