<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    use ApiResponses;

    /**
     * Get academic sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $sessions = [];

        // Get individual academic sessions
        $academicQuery = AcademicSession::where('academic_teacher_id', $academicTeacherId)
            ->with(['student.user', 'academicSubscription']);

        // Apply filters
        if ($request->filled('status')) {
            $academicQuery->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $academicQuery->whereDate('scheduled_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $academicQuery->whereDate('scheduled_at', '<=', $request->to_date);
        }

        $academicSessions = $academicQuery->orderBy('scheduled_at', 'desc')
            ->limit(50)
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'id' => $session->id,
                'type' => 'individual',
                'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                'student_name' => $session->student?->user?->name ?? 'طالب',
                'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_link' => $session->meeting_link,
            ];
        }

        // Get interactive course sessions
        $courseIds = $user->academicTeacherProfile->assignedCourses()
            ->pluck('id');

        $interactiveQuery = InteractiveCourseSession::whereIn('course_id', $courseIds)
            ->with(['course']);

        if ($request->filled('status')) {
            $interactiveQuery->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $interactiveQuery->whereDate('scheduled_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $interactiveQuery->whereDate('scheduled_at', '<=', $request->to_date);
        }

        $interactiveSessions = $interactiveQuery->orderBy('scheduled_at', 'desc')
            ->limit(50)
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = [
                'id' => $session->id,
                'type' => 'interactive',
                'title' => $session->title ?? $session->course?->title,
                'course_name' => $session->course?->title,
                'session_number' => $session->session_number,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_link' => $session->meeting_link,
            ];
        }

        // Sort and paginate
        usort($sessions, fn ($a, $b) => strtotime($b['scheduled_at']) <=> strtotime($a['scheduled_at']));

        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($sessions);
        $sessions = array_slice($sessions, ($page - 1) * $perPage, $perPage);

        return $this->success([
            'sessions' => array_values($sessions),
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get session detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        // Try to find academic session first
        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->with(['student.user', 'academicSubscription.subject', 'reports'])
            ->first();

        if ($session) {
            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'type' => 'individual',
                    'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                    'student' => $session->student?->user ? [
                        'id' => $session->student->user->id,
                        'name' => $session->student->user->name,
                        'avatar' => $session->student->user->avatar
                            ? asset('storage/'.$session->student->user->avatar)
                            : null,
                        'phone' => $session->student?->phone ?? $session->student->user->phone,
                    ] : null,
                    'subject' => $session->academicSubscription?->subject ? [
                        'id' => $session->academicSubscription->subject->id,
                        'name' => $session->academicSubscription->subject->name,
                    ] : [
                        'name' => $session->academicSubscription?->subject_name ?? 'غير محدد',
                    ],
                    'scheduled_at' => $session->scheduled_at?->toISOString(),
                    'duration_minutes' => $session->duration_minutes ?? 60,
                    'status' => $session->status->value ?? $session->status,
                    'meeting_link' => $session->meeting_link,
                    'homework' => $session->homework,
                    'lesson_content' => $session->lesson_content,
                    'topics_covered' => $session->topics_covered ?? [],
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

        // Try interactive course session
        $courseIds = $user->academicTeacherProfile->assignedCourses()
            ->pluck('id');

        $interactiveSession = InteractiveCourseSession::where('id', $id)
            ->whereIn('course_id', $courseIds)
            ->with(['course'])
            ->first();

        if ($interactiveSession) {
            return $this->success([
                'session' => [
                    'id' => $interactiveSession->id,
                    'type' => 'interactive',
                    'title' => $interactiveSession->title ?? $interactiveSession->course?->title,
                    'course' => $interactiveSession->course ? [
                        'id' => $interactiveSession->course->id,
                        'title' => $interactiveSession->course->title,
                    ] : null,
                    'session_number' => $interactiveSession->session_number,
                    'description' => $interactiveSession->description,
                    'scheduled_at' => $interactiveSession->scheduled_at?->toISOString(),
                    'duration_minutes' => $interactiveSession->duration_minutes ?? 60,
                    'status' => $interactiveSession->status->value ?? $interactiveSession->status,
                    'meeting_link' => $interactiveSession->meeting_link,
                    'materials' => $interactiveSession->materials ?? [],
                    'created_at' => $interactiveSession->created_at->toISOString(),
                ],
            ], __('Session retrieved successfully'));
        }

        return $this->notFound(__('Session not found.'));
    }

    /**
     * Complete a session.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
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
            'homework' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'lesson_content' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'topics_covered' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $session->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'homework' => $request->homework ?? $session->homework,
                'lesson_content' => $request->lesson_content ?? $session->lesson_content,
                'topics_covered' => $request->topics_covered ?? $session->topics_covered,
                'notes' => $request->notes ?? $session->notes,
            ]);

            // Create report if rating provided
            if ($request->filled('rating')) {
                AcademicSessionReport::updateOrCreate(
                    ['academic_session_id' => $session->id],
                    [
                        'rating' => $request->rating,
                        'teacher_feedback' => $request->feedback,
                    ]
                );
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
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
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
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
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
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->with(['student.user'])
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
                'student_name' => $session->student?->user?->name,
            ],
        ], __('Student marked as absent'));
    }

    /**
     * Update session evaluation.
     */
    public function updateEvaluation(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'homework' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'lesson_content' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'topics_covered' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'homework' => $request->homework ?? $session->homework,
            'lesson_content' => $request->lesson_content ?? $session->lesson_content,
            'topics_covered' => $request->topics_covered ?? $session->topics_covered,
            'notes' => $request->notes ?? $session->notes,
        ]);

        // Update or create report
        if ($request->filled('rating') || $request->filled('feedback')) {
            AcademicSessionReport::updateOrCreate(
                ['academic_session_id' => $session->id],
                [
                    'rating' => $request->rating ?? $session->reports?->first()?->rating,
                    'teacher_feedback' => $request->feedback ?? $session->reports?->first()?->teacher_feedback,
                ]
            );
        }

        return $this->success([
            'session' => [
                'id' => $session->id,
                'homework' => $session->homework,
                'lesson_content' => $session->lesson_content,
            ],
        ], __('Evaluation updated successfully'));
    }
}
