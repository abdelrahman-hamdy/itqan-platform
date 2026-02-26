<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrialRequestController extends Controller
{
    use ApiResponses;

    /**
     * Get all trial requests for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (! $student) {
            return $this->unauthorized(__('Student profile not found.'));
        }

        // quran_trial_requests.student_id references StudentProfile.id
        $query = QuranTrialRequest::where('student_id', $student->id)
            ->with(['quranTeacher.user', 'trialSession']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        return $this->success([
            'data' => collect($requests->items())->map(fn ($req) => $this->formatTrialRequest($req))->toArray(),
            'pagination' => PaginationHelper::fromPaginator($requests),
        ], __('Trial requests retrieved successfully'));
    }

    /**
     * Get a specific trial request.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (! $student) {
            return $this->unauthorized(__('Student profile not found.'));
        }

        $trialRequest = QuranTrialRequest::where('id', $id)
            ->where('student_id', $student->id)
            ->with(['quranTeacher.user', 'trialSession'])
            ->first();

        if (! $trialRequest) {
            return $this->notFound(__('Trial request not found.'));
        }

        return $this->success([
            'data' => $this->formatTrialRequest($trialRequest),
        ], __('Trial request retrieved successfully'));
    }

    /**
     * Create a new trial request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (! $student) {
            return $this->unauthorized(__('Student profile not found.'));
        }

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:quran_teacher_profiles,id'],
            'student_notes' => ['nullable', 'string', 'max:1000'],
            'current_level' => ['nullable', 'string', 'max:255'],
            'preferred_time' => ['nullable', 'string', 'max:255'],
            'learning_goals' => ['nullable', 'array'],
            'learning_goals.*' => ['string', 'max:255'],
        ]);

        $academy = $request->attributes->get('academy') ?? current_academy();

        // Check if teacher exists and is active
        $teacher = QuranTeacherProfile::where('id', $validated['teacher_id'])
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->first();

        if (! $teacher) {
            return $this->notFound(__('Teacher not found or not available.'));
        }

        // Check if student already has a pending/scheduled trial with this teacher
        $existingRequest = QuranTrialRequest::where('student_id', $student->id)
            ->where('teacher_id', $validated['teacher_id'])
            ->whereIn('status', ['pending', 'scheduled'])
            ->first();

        if ($existingRequest) {
            return $this->error(__('You already have an active trial request with this teacher.'), 422);
        }

        // Create the trial request
        $trialRequest = QuranTrialRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'teacher_id' => $validated['teacher_id'],
            'notes' => $validated['student_notes'] ?? null,
            'current_level' => $validated['current_level'] ?? null,
            'preferred_time' => $validated['preferred_time'] ?? null,
            'learning_goals' => $validated['learning_goals'] ?? null,
            'status' => 'pending',
        ]);

        $trialRequest->load(['quranTeacher.user', 'trialSession']);

        return $this->success([
            'data' => $this->formatTrialRequest($trialRequest),
        ], __('Trial request created successfully'), 201);
    }

    /**
     * Cancel a trial request.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (! $student) {
            return $this->unauthorized(__('Student profile not found.'));
        }

        $trialRequest = QuranTrialRequest::where('id', $id)
            ->where('student_id', $student->id)
            ->first();

        if (! $trialRequest) {
            return $this->notFound(__('Trial request not found.'));
        }

        // Only allow cancellation of pending or scheduled requests
        $statusValue = is_object($trialRequest->status) ? $trialRequest->status->value : $trialRequest->status;
        if (! in_array($statusValue, ['pending', 'scheduled'])) {
            return $this->error(__('This trial request cannot be cancelled.'), 422);
        }

        $trialRequest->cancel();

        return $this->success(null, __('Trial request cancelled successfully'));
    }

    /**
     * Format a trial request for API response.
     */
    private function formatTrialRequest(QuranTrialRequest $request): array
    {
        return [
            'id' => (string) $request->id,
            'teacher_id' => (string) $request->teacher_id,
            'teacher_name' => $request->quranTeacher?->user?->name ?? $request->quranTeacher?->full_name ?? 'Unknown',
            'teacher_avatar_url' => $request->quranTeacher?->user?->avatar
                ? asset('storage/'.$request->quranTeacher->user->avatar)
                : null,
            'status' => is_object($request->status) ? $request->status->value : $request->status,
            'student_notes' => $request->notes,
            'rejection_reason' => $request->feedback,
            'current_level' => $request->current_level,
            'preferred_time' => $request->preferred_time,
            'learning_goals' => $request->learning_goals ?? [],
            'scheduled_at' => $request->trialSession?->scheduled_at?->toIso8601String(),
            'requested_at' => $request->created_at->toIso8601String(),
            'completed_at' => $request->completed_at?->toIso8601String(),
            'created_at' => $request->created_at->toIso8601String(),
        ];
    }
}
