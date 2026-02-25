<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use Exception;
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
use Illuminate\Validation\Rule;

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
            ->with(['student', 'academicSubscription']);

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
                'student_name' => $session->student?->name ?? 'طالب',
                'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
            ];
        }

        // Get interactive course sessions
        $courseIds = collect();
        if ($user->academicTeacherProfile) {
            $courseIds = $user->academicTeacherProfile->assignedCourses()->pluck('id');
        }

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
                'meeting_url' => $session->meeting_link,
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
            ->with(['student', 'academicSubscription.subject', 'reports'])
            ->first();

        if ($session) {
            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'type' => 'individual',
                    'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                    'student' => $session->student ? [
                        'id' => $session->student->id,
                        'name' => $session->student->name,
                        'avatar' => $session->student->avatar
                            ? asset('storage/'.$session->student->avatar)
                            : null,
                        'phone' => $session->student->phone,
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
                    'meeting_url' => $session->meeting_link,
                    'homework' => $session->homework,
                    'lesson_content' => $session->lesson_content,
                    'topics_covered' => $session->topics_covered ?? [],
                    'notes' => $session->notes,
                    'teacher_notes' => $session->teacher_notes,
                    'report' => ($report = $session->reports?->first()) ? [
                        'id' => $report->id,
                        'rating' => $report->rating,
                        'notes' => $report->notes,
                        'teacher_feedback' => $report->teacher_feedback,
                    ] : null,
                    'started_at' => $session->started_at?->toISOString(),
                    'ended_at' => $session->ended_at?->toISOString(),
                    'created_at' => $session->created_at->toISOString(),
                ],
            ], __('Session retrieved successfully'));
        }

        // Try interactive course session
        $courseIds = $user->academicTeacherProfile?->assignedCourses()
            ?->pluck('id') ?? collect();

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
                    'meeting_url' => $interactiveSession->meeting_link,
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
        } catch (Exception $e) {
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

    /**
     * Get attendance records for a session (auto-tracked via LiveKit).
     * Handles both AcademicSession (individual) and InteractiveCourseSession (interactive).
     */
    public function attendance(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        // Try individual academic session first, then interactive
        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->first();

        $sessionType = 'academic';

        if (! $session) {
            $courseIds = $user->academicTeacherProfile?->assignedCourses()?->pluck('id') ?? collect();
            $session = InteractiveCourseSession::where('id', $id)
                ->whereIn('course_id', $courseIds)
                ->first();
            $sessionType = 'interactive';
        }

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Query meeting_attendances using DB to avoid enum cast issues
        $records = DB::table('meeting_attendances')
            ->join('users', 'meeting_attendances.user_id', '=', 'users.id')
            ->where('meeting_attendances.session_id', $session->id)
            ->where('meeting_attendances.session_type', $sessionType)
            ->where('meeting_attendances.user_type', 'student')
            ->select([
                'meeting_attendances.id',
                'meeting_attendances.user_id as student_id',
                'meeting_attendances.attendance_status as status_raw',
                'meeting_attendances.first_join_time as attended_at',
                'meeting_attendances.last_leave_time as left_at',
                'meeting_attendances.total_duration_minutes as duration_minutes',
                DB::raw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as student_name"),
                'users.avatar as avatar_path',
            ])
            ->get();

        $formatted = $records->map(function ($record) {
            return [
                'id'               => $record->id,
                'student_id'       => $record->student_id,
                'student_name'     => trim($record->student_name),
                'student_avatar'   => $record->avatar_path ? asset('storage/'.$record->avatar_path) : null,
                'status'           => $record->status_raw ?? 'absent',
                'attended_at'      => $record->attended_at,
                'left_at'          => $record->left_at,
                'duration_minutes' => $record->duration_minutes ?? 0,
                'is_manually_set'  => false,
                'override_reason'  => null,
            ];
        });

        $summary = [
            'total'    => $records->count(),
            'attended' => $records->where('status_raw', 'attended')->count(),
            'late'     => $records->where('status_raw', 'late')->count(),
            'absent'   => $records->where('status_raw', 'absent')->count(),
            'left'     => $records->where('status_raw', 'left')->count(),
        ];

        return $this->success([
            'attendance' => $formatted->values()->toArray(),
            'summary'    => $summary,
        ], __('Attendance retrieved successfully'));
    }

    /**
     * Override a student's attendance status (teacher correction).
     */
    public function overrideAttendance(Request $request, string $sessionId, string $attendanceId): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        // Resolve session (individual or interactive)
        $session = AcademicSession::where('id', $sessionId)
            ->where('academic_teacher_id', $academicTeacherId)
            ->first();

        $sessionType = 'academic';

        if (! $session) {
            $courseIds = $user->academicTeacherProfile?->assignedCourses()?->pluck('id') ?? collect();
            $session = InteractiveCourseSession::where('id', $sessionId)
                ->whereIn('course_id', $courseIds)
                ->first();
            $sessionType = 'interactive';
        }

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Verify attendance record belongs to this session
        $record = DB::table('meeting_attendances')
            ->where('id', $attendanceId)
            ->where('session_id', $session->id)
            ->where('session_type', $sessionType)
            ->first();

        if (! $record) {
            return $this->notFound(__('Attendance record not found.'));
        }

        $validated = $request->validate([
            'status'          => ['required', Rule::in(['attended', 'absent', 'late', 'left'])],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('meeting_attendances')
            ->where('id', $attendanceId)
            ->update([
                'attendance_status' => $validated['status'],
                'updated_at'        => now(),
            ]);

        return $this->success([], __('Attendance updated successfully'));
    }
}
