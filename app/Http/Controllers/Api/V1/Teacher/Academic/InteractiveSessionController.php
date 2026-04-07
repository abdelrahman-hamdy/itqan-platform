<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\HandlesAbsentReschedule;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InteractiveSessionController extends Controller
{
    use ApiResponses, HandlesAbsentReschedule;

    /**
     * Resolve an interactive course session belonging to the authenticated teacher.
     *
     * Verifies the session's course is assigned to the teacher's academic profile.
     * Optionally eager-loads the given relationships.
     */
    protected function resolveSession(int $id, \App\Models\User $user, array $with = []): ?InteractiveCourseSession
    {
        $courseIds = $user->academicTeacherProfile?->assignedCourses()?->pluck('id') ?? collect();

        if ($courseIds->isEmpty()) {
            return null;
        }

        $query = InteractiveCourseSession::where('id', $id)
            ->whereIn('course_id', $courseIds);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * List interactive sessions for the teacher's assigned courses.
     *
     * Filters: status, date_from, date_to
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $courseIds = $user->academicTeacherProfile->assignedCourses()->pluck('id');

        if ($courseIds->isEmpty()) {
            return $this->success([
                'sessions' => [],
                'pagination' => PaginationHelper::fromArray(0, 1, 15),
            ], __('Sessions retrieved successfully'));
        }

        $query = InteractiveCourseSession::whereIn('course_id', $courseIds)
            ->with(['course']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date_to);
        }

        $perPage = PaginationHelper::getPerPage($request);
        $sessions = $query->orderBy('scheduled_at', 'desc')->paginate($perPage);

        return $this->success([
            'sessions' => collect($sessions->items())->map(fn ($session) => [
                'id' => $session->id,
                'type' => 'interactive',
                'title' => $session->title ?? $session->course?->title,
                'course' => $session->course ? [
                    'id' => $session->course->id,
                    'title' => $session->course->title,
                ] : null,
                'session_number' => $session->session_number,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
                'attendance_count' => $session->attendance_count ?? 0,
                'homework_assigned' => (bool) $session->homework_assigned,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($sessions),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Show interactive session detail with course, attendance, homework, and notes.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user, ['course', 'homework', 'reports']);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'session' => [
                'id' => $session->id,
                'type' => 'interactive',
                'title' => $session->title ?? $session->course?->title,
                'course' => $session->course ? [
                    'id' => $session->course->id,
                    'title' => $session->course->title,
                ] : null,
                'session_number' => $session->session_number,
                'description' => $session->description,
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
                'lesson_content' => $session->lesson_content,
                'homework_assigned' => (bool) $session->homework_assigned,
                'homework_description' => $session->homework_description,
                'session_notes' => $session->session_notes,
                'teacher_feedback' => $session->teacher_feedback,
                'attendance_count' => $session->attendance_count ?? 0,
                'report' => ($report = $session->reports?->first()) ? [
                    'id' => $report->id,
                    'rating' => $report->rating,
                    'notes' => $report->notes,
                    'teacher_feedback' => $report->teacher_feedback,
                    'homework_degree' => $report->homework_degree,
                ] : null,
                'started_at' => $session->started_at?->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
                'created_at' => $session->created_at?->toISOString(),
            ],
        ], __('Session retrieved successfully'));
    }

    /**
     * Mark an interactive session as completed.
     *
     * Accepts optional homework, lesson_content, and notes.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

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
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
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

            if ($request->has('notes')) {
                $updateData['session_notes'] = $request->notes;
            }

            $session->update($updateData);

            // Create report if rating provided
            if ($request->filled('rating')) {
                InteractiveSessionReport::updateOrCreate(
                    ['session_id' => $session->id],
                    [
                        'academy_id' => $session->academy_id,
                        'rating' => $request->rating,
                        'teacher_feedback' => $request->feedback,
                    ]
                );
            }

            // Update attendance count
            $session->updateAttendanceCount();

            DB::commit();

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'status' => SessionStatus::COMPLETED->value,
                    'ended_at' => $session->ended_at->toISOString(),
                ],
            ], __('Session completed successfully'));
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(__('Failed to complete session.'), 500, 'COMPLETE_FAILED');
        }
    }

    /**
     * Cancel an interactive session with a reason.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

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
                'status' => SessionStatus::CANCELLED->value,
                'cancellation_reason' => $request->reason,
            ],
        ], __('Session cancelled successfully'));
    }

    /**
     * Reschedule an interactive session to a new time.
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

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

        $oldScheduledAt = $session->scheduled_at;

        $session->update([
            'scheduled_at' => $request->scheduled_at,
            'rescheduled_from' => $oldScheduledAt,
            'rescheduled_to' => $request->scheduled_at,
            'reschedule_reason' => $request->reason,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'scheduled_at' => $session->fresh()->scheduled_at->toISOString(),
                'rescheduled_from' => $oldScheduledAt?->toISOString(),
            ],
        ], __('Session rescheduled successfully'));
    }

    /**
     * Mark students absent for a group interactive session.
     *
     * Unlike individual sessions, interactive course sessions track absence
     * at the enrollment/attendance level, not on the session itself.
     * This endpoint marks all enrolled students who have no attendance record as absent.
     */
    public function markAbsent(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user, ['course.enrollments.student.user', 'attendances']);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue === SessionStatus::CANCELLED->value) {
            return $this->error(__('Cannot mark absent for a cancelled session.'), 400, 'SESSION_CANCELLED');
        }

        $validator = Validator::make($request->all(), [
            'student_ids' => ['sometimes', 'array'],
            'student_ids.*' => ['integer'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $existingAttendanceUserIds = $session->attendances->pluck('student_id')->toArray();
            $enrollments = $session->course?->enrollments ?? collect();

            // If specific student_ids provided, filter to those; otherwise mark all without attendance
            $targetStudentIds = $request->has('student_ids')
                ? $request->student_ids
                : $enrollments->pluck('student_id')->diff($existingAttendanceUserIds)->values()->toArray();

            $markedCount = 0;
            foreach ($targetStudentIds as $studentId) {
                // Check not already recorded
                if (! in_array($studentId, $existingAttendanceUserIds)) {
                    $session->attendances()->create([
                        'student_id' => $studentId,
                        'attendance_status' => AttendanceStatus::ABSENT->value,
                        'notes' => $request->reason,
                    ]);
                    $markedCount++;
                }
            }

            // Update attendance count
            $session->updateAttendanceCount();

            DB::commit();

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'marked_absent_count' => $markedCount,
                ],
            ], __('Students marked as absent'));
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(__('Failed to mark students as absent.'), 500, 'MARK_ABSENT_FAILED');
        }
    }

    /**
     * Update session evaluation (lesson content, homework, notes, rating).
     */
    public function updateEvaluation(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'lesson_content' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'homework_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
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

        if ($request->has('notes')) {
            $updateData['session_notes'] = $request->notes;
        }

        if (! empty($updateData)) {
            $session->update($updateData);
        }

        // Update or create report
        if ($request->filled('rating') || $request->filled('feedback') || $request->filled('homework_degree')) {
            InteractiveSessionReport::updateOrCreate(
                ['session_id' => $session->id],
                array_filter([
                    'academy_id' => $session->academy_id,
                    'rating' => $request->rating ?? $session->reports?->first()?->rating,
                    'teacher_feedback' => $request->feedback ?? $session->reports?->first()?->teacher_feedback,
                    'homework_degree' => $request->homework_degree ?? $session->reports?->first()?->homework_degree,
                ], fn ($v) => $v !== null)
            );
        }

        return $this->success([
            'session' => [
                'id' => $session->id,
                'lesson_content' => $session->lesson_content,
                'homework_description' => $session->homework_description,
                'homework_assigned' => (bool) $session->homework_assigned,
            ],
        ], __('Evaluation updated successfully'));
    }

    /**
     * Update session notes only.
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

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
            'session_notes' => $request->notes ?? $session->session_notes,
            'teacher_feedback' => $request->teacher_notes ?? $session->teacher_feedback,
        ]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'notes' => $session->session_notes,
                'teacher_notes' => $session->teacher_feedback,
            ],
        ], __('Notes updated successfully'));
    }

    /**
     * Get attendance records for an interactive session.
     *
     * Uses meeting_attendances table via DB query to avoid enum cast issues,
     * consistent with the Academic and Quran session controllers.
     */
    public function attendance(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Query meeting_attendances using DB to avoid enum cast issues
        $records = DB::table('meeting_attendances')
            ->join('users', 'meeting_attendances.user_id', '=', 'users.id')
            ->where('meeting_attendances.session_id', $session->id)
            ->where('meeting_attendances.academy_id', $session->academy_id)
            ->where('meeting_attendances.session_type', 'interactive')
            ->where('meeting_attendances.user_type', 'student')
            ->select([
                'meeting_attendances.id',
                'meeting_attendances.user_id as student_id',
                'meeting_attendances.attendance_status as status_raw',
                'meeting_attendances.first_join_time as attended_at',
                'meeting_attendances.last_leave_time as left_at',
                'meeting_attendances.total_duration_minutes as duration_minutes',
                'meeting_attendances.override_reason',
                'meeting_attendances.overridden_by',
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
                'is_manually_set' => ! empty($record->overridden_by),
                'override_reason' => $record->override_reason,
            ];
        });

        $summary = [
            'total' => $records->count(),
            'attended' => $records->where('status_raw', 'attended')->count(),
            'late' => $records->where('status_raw', 'late')->count(),
            'absent' => $records->where('status_raw', 'absent')->count(),
            'left' => $records->where('status_raw', 'left')->count(),
        ];

        return $this->success([
            'attendance' => $formatted->values()->toArray(),
            'summary' => $summary,
        ], __('Attendance retrieved successfully'));
    }

    /**
     * Override a single attendance record for an interactive session.
     */
    public function overrideAttendance(Request $request, int $id, int $attendanceId): JsonResponse
    {
        $user = $request->user();

        if (! $user->academicTeacherProfile) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = $this->resolveSession($id, $user);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Verify attendance record belongs to this session
        $record = DB::table('meeting_attendances')
            ->where('id', $attendanceId)
            ->where('session_id', $session->id)
            ->where('academy_id', $session->academy_id)
            ->where('session_type', 'interactive')
            ->first();

        if (! $record) {
            return $this->notFound(__('Attendance record not found.'));
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(AttendanceStatus::values())],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updateData = [
            'attendance_status' => $validated['status'],
            'override_reason' => $validated['override_reason'] ?? null,
            'overridden_by' => $user->id,
            'updated_at' => now(),
        ];

        DB::table('meeting_attendances')
            ->where('id', $attendanceId)
            ->where('academy_id', $session->academy_id)
            ->update($updateData);

        return $this->success([], __('Attendance updated successfully'));
    }
}
