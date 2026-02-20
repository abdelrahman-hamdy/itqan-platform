<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use App\Services\TrialNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrialRequestController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TrialNotificationService $notificationService
    ) {}

    /**
     * List trial requests for the authenticated Quran teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = QuranTrialRequest::where('teacher_id', $user->quranTeacherProfile->id)
            ->with(['student']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'data' => collect($requests->items())->map(fn ($req) => $this->formatTrialRequest($req))->toArray(),
            'pagination' => PaginationHelper::fromPaginator($requests),
        ], __('Trial requests retrieved successfully'));
    }

    /**
     * Show a single trial request.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $trialRequest = QuranTrialRequest::where('id', $id)
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->with(['student', 'trialSession'])
            ->first();

        if (! $trialRequest) {
            return $this->notFound(__('Trial request not found.'));
        }

        return $this->success([
            'data' => $this->formatTrialRequest($trialRequest),
        ], __('Trial request retrieved successfully'));
    }

    /**
     * Approve a pending trial request (teacher acknowledges the request).
     * The actual scheduling happens via the separate schedule endpoint.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $trialRequest = QuranTrialRequest::where('id', $id)
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->first();

        if (! $trialRequest) {
            return $this->notFound(__('Trial request not found.'));
        }

        if ($trialRequest->status !== TrialRequestStatus::PENDING) {
            return $this->error(__('Only pending trial requests can be approved.'), 422);
        }

        // Send approval notification
        try {
            $this->notificationService->sendTrialApprovedNotification($trialRequest);
        } catch (\Throwable $e) {
            Log::warning('Failed to send trial approved notification', [
                'trial_request_id' => $trialRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        $trialRequest->load(['student', 'trialSession']);

        return $this->success([
            'data' => $this->formatTrialRequest($trialRequest),
        ], __('Trial request approved successfully. Please schedule the session.'));
    }

    /**
     * Schedule a trial session for a pending trial request.
     */
    public function schedule(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $trialRequest = QuranTrialRequest::where('id', $id)
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->first();

        if (! $trialRequest) {
            return $this->notFound(__('Trial request not found.'));
        }

        if (! $trialRequest->canBeScheduled()) {
            return $this->error(__('This trial request cannot be scheduled.'), 422);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $session = DB::transaction(function () use ($trialRequest, $request, $user) {
                $profileId = $user->quranTeacherProfile->id;
                $scheduledAt = $request->input('scheduled_at');
                $date = date('Ymd', strtotime($scheduledAt));
                $time = date('Hi', strtotime($scheduledAt));

                $session = QuranSession::create([
                    'academy_id' => $trialRequest->academy_id,
                    'quran_teacher_id' => $user->id,
                    'student_id' => $trialRequest->student_id,
                    'session_type' => 'trial',
                    'trial_request_id' => $trialRequest->id,
                    'session_code' => "TR-{$profileId}-{$date}-{$time}",
                    'status' => SessionStatus::SCHEDULED,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => 30,
                ]);

                $trialRequest->update([
                    'trial_session_id' => $session->id,
                    'status' => TrialRequestStatus::SCHEDULED,
                ]);

                return $session;
            });

            // Send notification outside the transaction
            try {
                $trialRequest->refresh();
                $this->notificationService->sendTrialScheduledNotification($trialRequest, $session);
            } catch (\Throwable $e) {
                Log::warning('Failed to send trial scheduled notification', [
                    'trial_request_id' => $trialRequest->id,
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $trialRequest->load(['student', 'trialSession']);

            return $this->success([
                'data' => $this->formatTrialRequest($trialRequest),
            ], __('Trial session scheduled successfully'));
        } catch (\Throwable $e) {
            Log::error('Failed to schedule trial session', [
                'trial_request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError(__('Failed to schedule trial session.'));
        }
    }

    /**
     * Reject a pending trial request.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $trialRequest = QuranTrialRequest::where('id', $id)
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->first();

        if (! $trialRequest) {
            return $this->notFound(__('Trial request not found.'));
        }

        if ($trialRequest->status->isTerminal()) {
            return $this->error(__('This trial request cannot be rejected.'), 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $trialRequest->update([
            'status' => TrialRequestStatus::CANCELLED,
            'feedback' => $request->input('reason'),
        ]);

        // Send cancellation notification
        try {
            $trialRequest->refresh();
            $this->notificationService->sendTrialCancelledNotification($trialRequest);
        } catch (\Throwable $e) {
            Log::warning('Failed to send trial cancellation notification', [
                'trial_request_id' => $trialRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(null, __('Trial request rejected successfully'));
    }

    /**
     * Format a trial request for API response.
     */
    private function formatTrialRequest(QuranTrialRequest $req): array
    {
        return [
            'id' => (string) $req->id,
            'student_id' => (string) $req->student_id,
            'student_name' => $req->student?->name ?? $req->student_name,
            'student_avatar_url' => $req->student?->avatar ? asset('storage/'.$req->student->avatar) : null,
            'status' => is_object($req->status) ? $req->status->value : $req->status,
            'student_notes' => $req->notes,
            'current_level' => $req->current_level,
            'preferred_time' => $req->preferred_time,
            'learning_goals' => $req->learning_goals ?? [],
            'rating' => $req->rating,
            'feedback' => $req->feedback,
            'scheduled_at' => $req->trialSession?->scheduled_at?->toIso8601String(),
            'requested_at' => $req->created_at->toIso8601String(),
            'completed_at' => $req->completed_at?->toIso8601String(),
            'created_at' => $req->created_at->toIso8601String(),
        ];
    }
}
