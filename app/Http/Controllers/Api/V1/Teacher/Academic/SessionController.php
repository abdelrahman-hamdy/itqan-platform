<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\HandlesAbsentReschedule;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Services\SessionSettingsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SessionController extends Controller
{
    use ApiResponses, HandlesAbsentReschedule;

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

        // TODO: Replace with cursor-based pagination for teachers with many sessions
        $academicSessions = $academicQuery->orderBy('scheduled_at', 'desc')
            ->limit(500)
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
                'preparation_minutes' => app(SessionSettingsService::class)->getPreparationMinutes($session),
                'ending_buffer_minutes' => app(SessionSettingsService::class)->getBufferMinutes($session),
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
                // Drives the teacher-side display-status mapping in mobile.
                'counts_for_teacher' => $session->counts_for_teacher,
                'teacher_attendance_status' => $session->teacher_attendance_status,
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

        // TODO: Replace with cursor-based pagination for teachers with many sessions
        $interactiveSessions = $interactiveQuery->orderBy('scheduled_at', 'desc')
            ->limit(500)
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
                'preparation_minutes' => app(SessionSettingsService::class)->getPreparationMinutes($session),
                'ending_buffer_minutes' => app(SessionSettingsService::class)->getBufferMinutes($session),
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
                'counts_for_teacher' => $session->counts_for_teacher,
                'teacher_attendance_status' => $session->teacher_attendance_status,
            ];
        }

        // Sort and paginate
        usort($sessions, fn ($a, $b) => strtotime($b['scheduled_at']) <=> strtotime($a['scheduled_at']));

        $perPage = min((int) $request->get('per_page', 15), 50);
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
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        // Try to find academic session first
        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->with([
                'student',
                'academicSubscription.subject',
                'reports',
                'meetingAttendances' => fn ($q) => $q->where('user_type', 'student'),
            ])
            ->first();

        if ($session) {
            $studentRow = $session->student;
            $reportRow = $session->reports?->first();
            $studentAttendance = $studentRow ? $session->attendanceFor((int) $studentRow->id) : null;

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'type' => 'academic',
                    'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                    'student' => $studentRow ? [
                        'id' => $studentRow->id,
                        'name' => $studentRow->name,
                        'avatar' => $studentRow->avatar
                            ? asset('storage/'.$studentRow->avatar)
                            : null,
                        'phone' => $studentRow->phone,
                    ] : null,
                    'subject' => $session->academicSubscription?->subject ? [
                        'id' => $session->academicSubscription->subject->id,
                        'name' => $session->academicSubscription->subject->name,
                    ] : [
                        'name' => $session->academicSubscription?->subject_name ?? 'غير محدد',
                    ],
                    'scheduled_at' => $session->scheduled_at?->toISOString(),
                    'duration_minutes' => $session->duration_minutes ?? 60,
                    'preparation_minutes' => app(SessionSettingsService::class)->getPreparationMinutes($session),
                    'ending_buffer_minutes' => app(SessionSettingsService::class)->getBufferMinutes($session),
                    'status' => $session->status->value ?? $session->status,
                    'meeting_url' => $session->meeting_link,
                    'lesson_content' => $session->lesson_content,
                    'homework_description' => $session->homework_description,
                    'homework_file' => $session->homework_file,
                    'homework_assigned' => (bool) $session->homework_assigned,
                    'recording_url' => $session->recording_url,
                    'session_notes' => $session->session_notes,
                    'teacher_feedback' => $session->teacher_feedback,
                    'student_rating' => $session->student_rating,
                    'student_feedback' => $session->student_feedback,
                    'students' => $studentRow ? [[
                        'id' => $studentRow->id,
                        'name' => $studentRow->name,
                        'avatar' => $studentRow->avatar ? asset('storage/'.$studentRow->avatar) : null,
                    ]] : [],
                    'reports' => $studentRow ? [$this->formatAcademicReport($reportRow, $studentRow->id, $studentAttendance)] : [],
                    'counts_for_teacher' => $session->counts_for_teacher,
                    'teacher_attendance_status' => $session->teacher_attendance_status,
                    'started_at' => $session->started_at?->toISOString(),
                    'ended_at' => $session->ended_at?->toISOString(),
                    'created_at' => $session->created_at?->toISOString(),
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
                    'preparation_minutes' => app(SessionSettingsService::class)->getPreparationMinutes($interactiveSession),
                    'ending_buffer_minutes' => app(SessionSettingsService::class)->getBufferMinutes($interactiveSession),
                    'status' => $interactiveSession->status->value ?? $interactiveSession->status,
                    'meeting_url' => $interactiveSession->meeting_link,
                    'lesson_content' => $interactiveSession->lesson_content,
                    'homework_description' => $interactiveSession->homework_description,
                    'homework_file' => $interactiveSession->homework_file,
                    'homework_assigned' => (bool) $interactiveSession->homework_assigned,
                    'session_notes' => $interactiveSession->session_notes,
                    'teacher_feedback' => $interactiveSession->teacher_feedback,
                    'created_at' => $interactiveSession->created_at?->toISOString(),
                ],
            ], __('Session retrieved successfully'));
        }

        return $this->notFound(__('Session not found.'));
    }

    /**
     * Complete a session.
     */
    public function complete(Request $request, string $id): JsonResponse
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
            'lesson_content' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'homework_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'session_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'teacher_feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'homework_degree' => ['sometimes', 'numeric', 'min:0', 'max:10'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
            ];
            if ($request->has('lesson_content')) {
                $updateData['lesson_content'] = $request->lesson_content;
            }
            if ($request->has('homework_description')) {
                $updateData['homework_description'] = $request->homework_description;
                $updateData['homework_assigned'] = ! empty($request->homework_description);
            }
            if ($request->has('session_notes')) {
                $updateData['session_notes'] = $request->session_notes;
            }
            if ($request->has('teacher_feedback')) {
                $updateData['teacher_feedback'] = $request->teacher_feedback;
            }
            $session->update($updateData);

            // For 1:1 sessions, allow inline homework_degree write to per-student report.
            if ($session->student_id && $request->filled('homework_degree')) {
                AcademicSessionReport::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'student_id' => $session->student_id,
                    ],
                    [
                        'teacher_id' => $user->id,
                        'academy_id' => $session->academy_id,
                        'homework_degree' => $request->homework_degree,
                        'evaluated_at' => now(),
                        'manually_evaluated' => true,
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
    public function cancel(Request $request, string $id): JsonResponse
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
    public function reschedule(Request $request, string $id): JsonResponse
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

        if ($absentResponse = $this->rescheduleFromAbsentIfNeeded($session, $request, $user->id)) {
            return $absentResponse;
        }

        // Enforce teacher reschedule deadline
        $settingsService = app(\App\Services\SessionSettingsService::class);
        if ($settingsService->isRescheduleDeadlinePassed($session)) {
            return $this->error(
                __('scheduling.reschedule_deadline_passed', ['hours' => $settingsService->getTeacherRescheduleDeadlineHours($session)]),
                422,
                'RESCHEDULE_DEADLINE_PASSED'
            );
        }

        $oldScheduledAt = $session->scheduled_at;

        $session->update([
            'scheduled_at' => $request->scheduled_at,
            'rescheduled_from' => $oldScheduledAt,
            'rescheduled_at' => now(),
            'rescheduled_by' => $user->id,
            'reschedule_reason' => $request->reason,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'rescheduled_from' => $oldScheduledAt->toISOString(),
            ],
        ], __('Session rescheduled successfully'));
    }

    /**
     * Mark student absent for a session.
     */
    public function markAbsent(Request $request, string $id): JsonResponse
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
        if ($statusValue === SessionStatus::CANCELLED->value) {
            return $this->error(__('Cannot mark absent for a cancelled session.'), 400, 'SESSION_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $result = $session->markAsAbsent($request->reason);

        if (! $result) {
            return $this->error(__('Cannot mark this session as absent.'), 400, 'MARK_ABSENT_FAILED');
        }

        // Set the additional fields that markAsAbsent() doesn't handle
        $session->updateQuietly([
            'student_absent_at' => now(),
            'marked_absent_by' => $user->id,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'status' => SessionStatus::COMPLETED,
                'student_name' => $session->student?->name,
            ],
        ], __('Student marked as absent'));
    }

    /**
     * Update session evaluation.
     *
     * Writes to real columns: lesson_content, homework_description, session_notes,
     * teacher_feedback on the session, and homework_degree on the per-student report.
     */
    public function updateEvaluation(Request $request, string $id): JsonResponse
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
            'lesson_content' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'homework_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'session_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'teacher_feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'homework_degree' => ['sometimes', 'numeric', 'min:0', 'max:10'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $updateData = [];
        if ($request->has('lesson_content')) {
            $updateData['lesson_content'] = $request->lesson_content;
        }
        if ($request->has('homework_description')) {
            $updateData['homework_description'] = $request->homework_description;
            $updateData['homework_assigned'] = ! empty($request->homework_description);
        }
        if ($request->has('session_notes')) {
            $updateData['session_notes'] = $request->session_notes;
        }
        if ($request->has('teacher_feedback')) {
            $updateData['teacher_feedback'] = $request->teacher_feedback;
        }
        if (! empty($updateData)) {
            $session->update($updateData);
        }

        if ($session->student_id && $request->filled('homework_degree')) {
            AcademicSessionReport::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $session->student_id,
                ],
                [
                    'teacher_id' => $user->id,
                    'academy_id' => $session->academy_id,
                    'homework_degree' => $request->homework_degree,
                    'evaluated_at' => now(),
                    'manually_evaluated' => true,
                ]
            );
        }

        return $this->success([
            'session' => [
                'id' => $session->id,
                'lesson_content' => $session->lesson_content,
                'homework_description' => $session->homework_description,
                'session_notes' => $session->session_notes,
                'teacher_feedback' => $session->teacher_feedback,
            ],
        ], __('Evaluation updated successfully'));
    }

    /**
     * Update session notes.
     */
    public function updateNotes(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        // Try individual academic session first
        $session = AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->first();

        if (! $session) {
            // Try interactive course session
            $courseIds = $user->academicTeacherProfile?->assignedCourses()?->pluck('id') ?? collect();
            $session = InteractiveCourseSession::where('id', $id)
                ->whereIn('course_id', $courseIds)
                ->first();
        }

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'session_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'teacher_feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update([
            'session_notes' => $request->input('session_notes', $session->session_notes),
            'teacher_feedback' => $request->input('teacher_feedback', $session->teacher_feedback),
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'session_notes' => $session->session_notes,
                'teacher_feedback' => $session->teacher_feedback,
            ],
        ], __('Notes updated successfully'));
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
            ->where('meeting_attendances.academy_id', $session->academy_id)
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
                'id' => $record->id,
                'student_id' => $record->student_id,
                'student_name' => trim($record->student_name),
                'student_avatar' => $record->avatar_path ? asset('storage/'.$record->avatar_path) : null,
                'status' => $record->status_raw ?? 'absent',
                'attended_at' => $record->attended_at,
                'left_at' => $record->left_at,
                'duration_minutes' => $record->duration_minutes ?? 0,
                'is_manually_set' => false,
                'override_reason' => null,
            ];
        });

        $summary = [
            'total' => $records->count(),
            'attended' => $records->where('status_raw', 'attended')->count(),
            'partially_attended' => $records->where('status_raw', 'partially_attended')->count(),
            'absent' => $records->where('status_raw', 'absent')->count(),
            'late' => $records->where('status_raw', 'late')->count(),
            'left' => $records->where('status_raw', 'left')->count(),
        ];

        return $this->success([
            'attendance' => $formatted->values()->toArray(),
            'summary' => $summary,
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
            ->where('academy_id', $session->academy_id)
            ->where('session_type', $sessionType)
            ->first();

        if (! $record) {
            return $this->notFound(__('Attendance record not found.'));
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(AttendanceStatus::writableValues())],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updateData = [
            'attendance_status' => $validated['status'],
            'updated_at' => now(),
        ];

        if (! empty($validated['override_reason'])) {
            $updateData['override_reason'] = $validated['override_reason'];
            $updateData['overridden_by'] = $user->id;
        }

        DB::table('meeting_attendances')
            ->where('id', $attendanceId)
            ->where('academy_id', $session->academy_id)
            ->update($updateData);

        return $this->success([], __('Attendance updated successfully'));
    }

    /**
     * Format an academic per-student report (homework_degree + notes + attendance).
     */
    protected function formatAcademicReport(?AcademicSessionReport $report, ?int $studentId = null, ?\App\Models\MeetingAttendance $attendance = null): ?array
    {
        if (! $report && $studentId === null) {
            return null;
        }

        return [
            'id' => $report?->id,
            'student_id' => $report?->student_id ?? $studentId,
            'attendance_status' => $report?->attendance_status,
            'attendance_percentage' => $report?->attendance_percentage,
            'actual_attendance_minutes' => $report?->actual_attendance_minutes,
            'homework_degree' => $report?->homework_degree,
            'overall_performance' => $report?->overall_performance,
            'notes' => $report?->notes,
            'evaluated_at' => $report?->evaluated_at?->toISOString(),
            // Per-student counting flag for the supervisor toggle UI / mobile.
            'counts_for_subscription' => $attendance?->counts_for_subscription,
        ];
    }
}
