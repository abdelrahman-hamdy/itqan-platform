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
            ->with(['quranTeacher.user']);

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
            ->with(['quranTeacher.user'])
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

        // Check if student already has a pending/approved trial with this teacher
        $existingRequest = QuranTrialRequest::where('student_id', $student->id)
            ->where('quran_teacher_profile_id', $validated['teacher_id'])
            ->whereIn('status', ['pending', 'approved', 'scheduled'])
            ->first();

        if ($existingRequest) {
            return $this->error(__('You already have an active trial request with this teacher.'), 422);
        }

        // Create the trial request
        $trialRequest = QuranTrialRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'quran_teacher_profile_id' => $validated['teacher_id'],
            'student_notes' => $validated['student_notes'] ?? null,
            'current_level' => $validated['current_level'] ?? null,
            'preferred_time' => $validated['preferred_time'] ?? null,
            'learning_goals' => $validated['learning_goals'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $trialRequest->load(['quranTeacher.user']);

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

        // Only allow cancellation of pending or approved requests
        if (! in_array($trialRequest->status, ['pending', 'approved', 'scheduled'])) {
            return $this->error(__('This trial request cannot be cancelled.'), 422);
        }

        $trialRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 'student',
        ]);

        return $this->success(null, __('Trial request cancelled successfully'));
    }

    /**
     * Format a trial request for API response.
     */
    private function formatTrialRequest(QuranTrialRequest $request): array
    {
        return [
            'id' => (string) $request->id,
            'teacher_id' => (string) $request->quran_teacher_profile_id,
            'teacher_name' => $request->quranTeacher?->user?->name ?? $request->quranTeacher?->full_name ?? 'Unknown',
            'teacher_avatar_url' => $request->quranTeacher?->user?->avatar
                ? asset('storage/'.$request->quranTeacher->user->avatar)
                : null,
            'status' => $request->status,
            'student_notes' => $request->student_notes,
            'teacher_notes' => $request->teacher_notes,
            'current_level' => $request->current_level,
            'preferred_time' => $request->preferred_time,
            'learning_goals' => $request->learning_goals ?? [],
            'rejection_reason' => $request->rejection_reason,
            'scheduled_at' => $request->scheduled_at?->toIso8601String(),
            'requested_at' => $request->requested_at?->toIso8601String() ?? $request->created_at->toIso8601String(),
            'approved_at' => $request->approved_at?->toIso8601String(),
            'completed_at' => $request->completed_at?->toIso8601String(),
            'cancelled_at' => $request->cancelled_at?->toIso8601String(),
            'created_at' => $request->created_at->toIso8601String(),
        ];
    }
}
