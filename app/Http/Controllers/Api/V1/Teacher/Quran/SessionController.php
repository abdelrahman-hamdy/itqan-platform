<?php

namespace App\Http\Controllers\Api\V1\Teacher\Quran;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    use ApiResponses;

    /**
     * Get Quran sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = QuranSession::where('quran_teacher_id', $quranTeacherId)
            ->with(['student', 'individualCircle', 'circle']);

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
            'sessions' => collect($sessions->items())->map(fn ($session) => [
                'id' => $session->id,
                'title' => $session->title ?? 'جلسة قرآنية',
                'student_name' => $session->student?->name ?? $session->student?->full_name,
                'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                'circle_type' => $session->circle_id ? 'group' : 'individual',
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_link' => $session->meeting_link,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($sessions),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get session detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['student', 'individualCircle', 'circle', 'reports', 'subscription'])
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'session' => [
                'id' => $session->id,
                'title' => $session->title ?? 'جلسة قرآنية',
                'student' => $session->student ? [
                    'id' => $session->student->id,
                    'name' => $session->student->name,
                    'avatar' => $session->student->avatar
                        ? asset('storage/'.$session->student->avatar)
                        : null,
                    'phone' => $session->student->phone,
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
                'evaluation' => $this->formatEvaluation($session),
                'notes' => $session->notes,
                'teacher_notes' => $session->teacher_notes,
                'report' => $this->formatReport($session->reports?->first()),
                'started_at' => $session->started_at?->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
                'created_at' => $session->created_at->toISOString(),
            ],
        ], __('Session retrieved successfully'));
    }

    /**
     * Complete a session.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === SessionStatus::COMPLETED->value) {
            return $this->error(__('Session is already completed.'), 400, 'ALREADY_COMPLETED');
        }

        if ($statusValue === SessionStatus::CANCELLED->value) {
            return $this->error(__('Cannot complete a cancelled session.'), 400, 'SESSION_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'memorization_degree' => ['sometimes', 'numeric', 'min:0', 'max:10'],
            'revision_degree' => ['sometimes', 'numeric', 'min:0', 'max:10'],
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
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'verses_memorized' => $request->verses_memorized ?? $session->verses_memorized,
                'pages_reviewed' => $request->pages_reviewed ?? $session->pages_reviewed,
                'notes' => $request->notes ?? $session->notes,
                'quran_homework_memorization' => $request->homework_memorization ?? $session->quran_homework_memorization,
                'quran_homework_recitation' => $request->homework_recitation ?? $session->quran_homework_recitation,
                'quran_homework_review' => $request->homework_review ?? $session->quran_homework_review,
            ]);

            // Create or update session report with evaluation
            if ($request->filled('memorization_degree') || $request->filled('revision_degree')) {
                $this->updateOrCreateReport($session, $user, $request);
            }

            // Update subscription usage
            if (method_exists($session, 'updateSubscriptionUsage')) {
                $session->updateSubscriptionUsage();
            }

            DB::commit();

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'status' => SessionStatus::COMPLETED,
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
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === SessionStatus::COMPLETED->value) {
            return $this->error(__('Cannot cancel a completed session.'), 400, 'SESSION_COMPLETED');
        }

        if ($statusValue === SessionStatus::CANCELLED->value) {
            return $this->error(__('Session is already cancelled.'), 400, 'ALREADY_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $request->reason,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'status' => SessionStatus::CANCELLED,
                'cancellation_reason' => $request->reason,
            ],
        ], __('Session cancelled successfully'));
    }

    /**
     * Reschedule a session.
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === SessionStatus::COMPLETED->value) {
            return $this->error(__('Cannot reschedule a completed session.'), 400, 'SESSION_COMPLETED');
        }

        if ($statusValue === SessionStatus::CANCELLED->value) {
            return $this->error(__('Cannot reschedule a cancelled session.'), 400, 'SESSION_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $oldScheduledAt = $session->scheduled_at;

        $session->update([
            'scheduled_at' => $request->scheduled_at,
            'rescheduled_from' => $oldScheduledAt,
            'rescheduled_at' => now(),
            'rescheduled_by' => $user->id,
            'rescheduling_reason' => $request->reason,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'scheduled_at' => $session->scheduled_at->toISOString(),
                'rescheduled_from' => $oldScheduledAt->toISOString(),
            ],
        ], __('Session rescheduled successfully'));
    }

    /**
     * Mark student absent for a session.
     */
    public function markAbsent(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['student'])
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === SessionStatus::COMPLETED->value) {
            return $this->error(__('Cannot mark absent for a completed session.'), 400, 'SESSION_COMPLETED');
        }

        if ($statusValue === SessionStatus::CANCELLED->value) {
            return $this->error(__('Cannot mark absent for a cancelled session.'), 400, 'SESSION_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'status' => SessionStatus::ABSENT,
            'student_absent_at' => now(),
            'student_absence_reason' => $request->reason,
            'marked_absent_by' => $user->id,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'status' => SessionStatus::ABSENT,
                'student_name' => $session->student?->name,
            ],
        ], __('Student marked as absent'));
    }

    /**
     * Submit session evaluation.
     */
    public function evaluate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with('reports')
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'memorization_degree' => ['required', 'numeric', 'min:0', 'max:10'],
            'revision_degree' => ['sometimes', 'numeric', 'min:0', 'max:10'],
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

        DB::beginTransaction();
        try {
            // Update session progress fields
            $session->update([
                'verses_memorized' => $request->verses_memorized ?? $session->verses_memorized,
                'pages_reviewed' => $request->pages_reviewed ?? $session->pages_reviewed,
                'teacher_notes' => $request->feedback ?? $session->teacher_notes,
                'quran_homework_memorization' => $request->homework_memorization ?? $session->quran_homework_memorization,
                'quran_homework_recitation' => $request->homework_recitation ?? $session->quran_homework_recitation,
                'quran_homework_review' => $request->homework_review ?? $session->quran_homework_review,
            ]);

            // Create or update session report with evaluation degrees
            $report = $this->updateOrCreateReport($session, $user, $request);

            DB::commit();

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'evaluation' => $this->formatEvaluation($session->fresh(['reports'])),
                ],
            ], __('Evaluation submitted successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(__('Failed to submit evaluation.'), 500, 'EVALUATION_FAILED');
        }
    }

    /**
     * Update session notes.
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (! $quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (! $session) {
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

    /**
     * Format evaluation data from session and its report.
     */
    protected function formatEvaluation(QuranSession $session): array
    {
        $report = $session->reports?->first();

        return [
            'memorization_degree' => $report?->new_memorization_degree,
            'revision_degree' => $report?->reservation_degree,
            'overall_performance' => $report?->overall_performance,
            'verses_memorized' => $session->verses_memorized,
            'pages_reviewed' => $session->pages_reviewed,
            'evaluated_at' => $report?->evaluated_at?->toISOString(),
        ];
    }

    /**
     * Format report data.
     */
    protected function formatReport(?StudentSessionReport $report): ?array
    {
        if (! $report) {
            return null;
        }

        return [
            'id' => $report->id,
            'memorization_degree' => $report->new_memorization_degree,
            'revision_degree' => $report->reservation_degree,
            'overall_performance' => $report->overall_performance,
            'notes' => $report->notes,
            'evaluated_at' => $report->evaluated_at?->toISOString(),
        ];
    }

    /**
     * Create or update session report with evaluation.
     */
    protected function updateOrCreateReport(QuranSession $session, $user, Request $request): StudentSessionReport
    {
        return StudentSessionReport::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $session->student_id,
            ],
            [
                'teacher_id' => $user->id,
                'academy_id' => $session->academy_id,
                'new_memorization_degree' => $request->memorization_degree,
                'reservation_degree' => $request->revision_degree,
                'notes' => $request->feedback ?? $request->notes,
                'evaluated_at' => now(),
                'manually_evaluated' => true,
            ]
        );
    }
}
