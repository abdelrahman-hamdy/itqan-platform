<?php

namespace App\Http\Controllers\Api\V1\Teacher\Quran;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\HandlesAbsentReschedule;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\StudentSessionReport;
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
     * Get Quran sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

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
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return $this->success([
            'sessions' => collect($sessions->items())->map(fn ($session) => [
                'id' => $session->id,
                'title' => $session->title ?? 'جلسة قرآنية',
                'student_name' => $session->student?->name ?? $session->student?->full_name,
                'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                'circle_type' => $session->circle_id ? 'group' : 'individual',
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 60,
                'preparation_minutes' => app(SessionSettingsService::class)->getPreparationMinutes($session),
                'ending_buffer_minutes' => app(SessionSettingsService::class)->getBufferMinutes($session),
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
                // Drives the teacher-side "absent / completed" badge derivation.
                'counts_for_teacher' => $session->counts_for_teacher,
                'teacher_attendance_status' => $session->teacher_attendance_status,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($sessions),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get session detail.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with([
                'student',
                'individualCircle',
                'circle.students',
                'reports',
                'subscription',
                'sessionHomework',
                // Per-student attendance row drives the per-student counting flag
                // surfaced in the report payload below.
                'meetingAttendances' => fn ($q) => $q->where('user_type', 'student'),
            ])
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $students = $session->getStudentsForSession();
        $reportsByStudent = $session->reports->keyBy('student_id');

        return $this->success([
            'session' => [
                'id' => $session->id,
                'type' => 'quran',
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
                'preparation_minutes' => app(SessionSettingsService::class)->getPreparationMinutes($session),
                'ending_buffer_minutes' => app(SessionSettingsService::class)->getBufferMinutes($session),
                'status' => $session->status->value ?? $session->status,
                'meeting_url' => $session->meeting_link,
                'lesson_content' => $session->lesson_content,
                'session_notes' => $session->session_notes,
                'teacher_feedback' => $session->teacher_feedback,
                'student_rating' => $session->student_rating,
                'student_feedback' => $session->student_feedback,
                'homework' => $this->formatQuranHomework($session->sessionHomework),
                'students' => $students->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'avatar' => $s->avatar ? asset('storage/'.$s->avatar) : null,
                ])->values()->toArray(),
                'reports' => $students->map(fn ($s) => $this->formatReport(
                    $reportsByStudent->get($s->id),
                    $s->id,
                    $session->attendanceFor((int) $s->id),
                ))
                    ->filter()
                    ->values()
                    ->toArray(),
                'counts_for_teacher' => $session->counts_for_teacher,
                'teacher_attendance_status' => $session->teacher_attendance_status,
                'started_at' => $session->started_at?->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
                'created_at' => $session->created_at?->toISOString(),
            ],
        ], __('Session retrieved successfully'));
    }

    /**
     * Complete a session.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

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
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'lesson_content' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $session->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'lesson_content' => $request->lesson_content ?? $session->lesson_content,
                'session_notes' => $request->notes ?? $session->session_notes,
            ]);

            // For 1:1 sessions, accept inline degrees and write to the per-student report.
            // Group circles use the per-student report endpoint (PUT /reports/{studentId}).
            if ($session->student_id && ($request->filled('memorization_degree') || $request->filled('revision_degree'))) {
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

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

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
    public function reschedule(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

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

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
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
     * Submit session evaluation.
     */
    public function evaluate(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with('reports')
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        if (! $session->student_id) {
            return $this->error(
                __('Use the per-student report endpoint for group sessions.'),
                422,
                'PER_STUDENT_REPORT_REQUIRED'
            );
        }

        $validator = Validator::make($request->all(), [
            'memorization_degree' => ['required', 'numeric', 'min:0', 'max:10'],
            'revision_degree' => ['sometimes', 'numeric', 'min:0', 'max:10'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            if ($request->filled('feedback')) {
                $session->update([
                    'teacher_feedback' => $request->feedback,
                ]);
            }

            $report = $this->updateOrCreateReport($session, $user, $request);

            DB::commit();

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'evaluation' => $this->formatEvaluation($session->fresh(['reports'])),
                ],
            ], __('Evaluation submitted successfully'));
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(__('Failed to submit evaluation.'), 500, 'EVALUATION_FAILED');
        }
    }

    /**
     * Update session notes.
     */
    public function updateNotes(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

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
     */
    public function attendance(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Query meeting_attendances using DB to avoid enum cast issues
        // QuranSession records appear as session_type 'individual' or 'group'
        $records = DB::table('meeting_attendances')
            ->join('users', 'meeting_attendances.user_id', '=', 'users.id')
            ->where('meeting_attendances.session_id', $session->id)
            ->where('meeting_attendances.academy_id', $session->academy_id)
            ->whereIn('meeting_attendances.session_type', ['individual', 'group'])
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

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Verify attendance record belongs to this session
        $record = DB::table('meeting_attendances')
            ->where('id', $attendanceId)
            ->where('session_id', $session->id)
            ->where('academy_id', $session->academy_id)
            ->whereIn('session_type', ['individual', 'group'])
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
     * Format evaluation data from session and its report.
     */
    protected function formatEvaluation(QuranSession $session): array
    {
        $report = $session->reports?->first();

        return [
            'memorization_degree' => $report?->new_memorization_degree,
            'revision_degree' => $report?->reservation_degree,
            'overall_performance' => $report?->overall_performance,
            'evaluated_at' => $report?->evaluated_at?->toISOString(),
        ];
    }

    /**
     * Format report data, optionally seeded with a student id when no row exists yet.
     */
    protected function formatReport(?StudentSessionReport $report, ?int $studentId = null, ?\App\Models\MeetingAttendance $attendance = null): ?array
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
            'memorization_degree' => $report?->new_memorization_degree,
            'revision_degree' => $report?->reservation_degree,
            'overall_performance' => $report?->overall_performance,
            'notes' => $report?->notes,
            'evaluated_at' => $report?->evaluated_at?->toISOString(),
            // Per-student counting flag — feeds the supervisor toggle UI and the
            // mobile teacher view's per-student "absent / canceled" labels.
            'counts_for_subscription' => $attendance?->counts_for_subscription,
        ];
    }

    /**
     * Format Quran homework (oral evaluation, no submissions).
     */
    protected function formatQuranHomework(?QuranSessionHomework $homework): ?array
    {
        if (! $homework) {
            return null;
        }

        return [
            'id' => $homework->id,
            'has_new_memorization' => (bool) $homework->has_new_memorization,
            'has_review' => (bool) $homework->has_review,
            'has_comprehensive_review' => (bool) $homework->has_comprehensive_review,
            'new_memorization_pages' => $homework->new_memorization_pages,
            'new_memorization_surah' => $homework->new_memorization_surah,
            'new_memorization_from_verse' => $homework->new_memorization_from_verse,
            'new_memorization_to_verse' => $homework->new_memorization_to_verse,
            'review_pages' => $homework->review_pages,
            'review_surah' => $homework->review_surah,
            'review_from_verse' => $homework->review_from_verse,
            'review_to_verse' => $homework->review_to_verse,
            'comprehensive_review_surahs' => $homework->comprehensive_review_surahs ?? [],
            'additional_instructions' => $homework->additional_instructions,
            'due_date' => $homework->due_date?->toDateString(),
            'difficulty_level' => $homework->difficulty_level,
            'is_active' => (bool) $homework->is_active,
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
