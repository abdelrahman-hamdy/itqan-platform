<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\BaseSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Per-student session report editor for the mobile app.
 *
 * Mirrors the web's Teacher\StudentReportController::updateReport() flow:
 * the teacher edits one student's attendance + degrees + notes inline from
 * the session detail screen.
 *
 * Three session types are supported via the {type} segment: quran | academic | interactive.
 * Each maps to its own session model and report model.
 */
class SessionReportController extends Controller
{
    use ApiResponses;

    /**
     * GET /teacher/{type}/sessions/{id}/reports
     *
     * Returns one entry per enrolled student. For students that have no row yet
     * the entry contains nulls for the grading fields so the mobile UI can render
     * empty cards consistently.
     */
    public function index(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $session = $this->resolveSession($type, $id, $user);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $students = $this->getStudentsForSession($type, $session);
        $reports = $session->reports()->get()->keyBy('student_id');

        $payload = $students->map(fn (User $student) => $this->formatReport(
            $type,
            $reports->get($student->id),
            $student,
        ))->values()->toArray();

        return $this->success([
            'reports' => $payload,
            'lesson_content' => $session->lesson_content,
        ], __('Reports retrieved successfully'));
    }

    /**
     * PUT /teacher/{type}/sessions/{id}/reports/{studentId}
     *
     * Upserts the per-student report. Accepts only fields that exist on the
     * matching report model; AttendanceStatus is restricted to writableValues().
     */
    public function update(Request $request, string $type, int $id, int $studentId): JsonResponse
    {
        $user = $request->user();
        $session = $this->resolveSession($type, $id, $user);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $student = $this->getStudentsForSession($type, $session)->firstWhere('id', $studentId);

        if (! $student) {
            return $this->notFound(__('Student not found in this session.'));
        }

        $rules = [
            'attendance_status' => ['sometimes', 'nullable', Rule::in(AttendanceStatus::writableValues())],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];

        if ($type === 'quran') {
            $rules['memorization_degree'] = ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10'];
            $rules['revision_degree'] = ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10'];
        } else {
            $rules['homework_degree'] = ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $payload = [
            'teacher_id' => $user->id,
            'academy_id' => $session->academy_id,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ];

        // A null `attendance_status` means "revert to auto" — mirrors the web
        // flow in `Teacher\StudentReportController::updateEvaluation`. Without
        // this branch the mobile "Auto" choice was a silent no-op.
        $attendanceReset = false;
        if ($request->has('attendance_status')) {
            $incoming = $request->input('attendance_status');
            $attendanceReset = $incoming === null || $incoming === '';
            $payload['attendance_status'] = $attendanceReset ? null : $incoming;
            $payload['manually_evaluated'] = ! $attendanceReset;
        }
        if ($request->has('notes')) {
            $payload['notes'] = $request->input('notes');
        }

        if ($type === 'quran') {
            if ($request->has('memorization_degree')) {
                $payload['new_memorization_degree'] = $request->input('memorization_degree');
            }
            if ($request->has('revision_degree')) {
                $payload['reservation_degree'] = $request->input('revision_degree');
            }
        } else {
            if ($request->has('homework_degree')) {
                $payload['homework_degree'] = $request->input('homework_degree');
            }
        }

        $reportModel = $this->reportModelFor($type);
        $report = $reportModel::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $student->id,
            ],
            $payload,
        );

        if ($attendanceReset) {
            $report->refresh();
            $report->syncFromMeetingAttendance();
        }

        return $this->success([
            'report' => $this->formatReport($type, $report->fresh(), $student),
        ], __('Report updated successfully'));
    }

    /**
     * PUT /teacher/{type}/sessions/{id}/lesson-content
     *
     * Updates the session's `lesson_content` text. Used by the Details tab.
     */
    public function updateLessonContent(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $session = $this->resolveSession($type, $id, $user);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $validator = Validator::make($request->all(), [
            'lesson_content' => ['present', 'nullable', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session->update(['lesson_content' => $request->input('lesson_content')]);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'lesson_content' => $session->lesson_content,
            ],
        ], __('Lesson content updated successfully'));
    }

    /**
     * Resolve a session by type, scoped to the teacher's profile.
     */
    protected function resolveSession(string $type, int $id, User $user)
    {
        return match ($type) {
            'quran' => $this->resolveQuranSession($id, $user),
            'academic' => $this->resolveAcademicSession($id, $user),
            'interactive' => $this->resolveInteractiveSession($id, $user),
            default => null,
        };
    }

    protected function resolveQuranSession(int $id, User $user): ?QuranSession
    {
        if (! $user->quranTeacherProfile) {
            return null;
        }

        return QuranSession::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->with(['student', 'circle.students', 'individualCircle'])
            ->first();
    }

    protected function resolveAcademicSession(int $id, User $user): ?AcademicSession
    {
        $profileId = $user->academicTeacherProfile?->id;
        if (! $profileId) {
            return null;
        }

        return AcademicSession::where('id', $id)
            ->where('academic_teacher_id', $profileId)
            ->with(['student'])
            ->first();
    }

    protected function resolveInteractiveSession(int $id, User $user): ?InteractiveCourseSession
    {
        $courseIds = $user->academicTeacherProfile?->assignedCourses()?->pluck('id') ?? collect();
        if ($courseIds->isEmpty()) {
            return null;
        }

        return InteractiveCourseSession::where('id', $id)
            ->whereIn('course_id', $courseIds)
            ->with(['course'])
            ->first();
    }

    /**
     * Return the list of students who should appear on the report editor.
     */
    protected function getStudentsForSession(string $type, $session)
    {
        if ($type === 'quran' && method_exists($session, 'getStudentsForSession')) {
            return $session->getStudentsForSession();
        }

        if ($type === 'academic' && $session->student) {
            return collect([$session->student]);
        }

        if ($type === 'interactive') {
            $course = $session->course;
            if (! $course) {
                return collect();
            }

            return $course->enrollments()
                ->with('student.user')
                ->limit(500)
                ->get()
                ->map(fn ($enrollment) => $enrollment->student?->user)
                ->filter()
                ->values();
        }

        return collect();
    }

    protected function reportModelFor(string $type): string
    {
        return match ($type) {
            'quran' => StudentSessionReport::class,
            'academic' => AcademicSessionReport::class,
            'interactive' => InteractiveSessionReport::class,
        };
    }

    /**
     * Build the JSON payload for a single per-student report row.
     */
    protected function formatReport(string $type, ?BaseSessionReport $report, User $student): array
    {
        $base = [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'avatar' => $student->avatar ? asset('storage/'.$student->avatar) : null,
            ],
            'id' => $report?->id,
            'attendance_status' => $report?->attendance_status?->value ?? $report?->attendance_status,
            'attendance_percentage' => $report?->attendance_percentage,
            'actual_attendance_minutes' => $report?->actual_attendance_minutes,
            'meeting_enter_time' => $report?->meeting_enter_time?->toISOString(),
            'meeting_leave_time' => $report?->meeting_leave_time?->toISOString(),
            'is_late' => (bool) ($report?->is_late ?? false),
            'late_minutes' => $report?->late_minutes,
            'notes' => $report?->notes,
            'overall_performance' => $report?->overall_performance,
            'evaluated_at' => $report?->evaluated_at?->toISOString(),
            'manually_evaluated' => (bool) ($report?->manually_evaluated ?? false),
        ];

        if ($type === 'quran' && $report instanceof StudentSessionReport) {
            $base['memorization_degree'] = $report->new_memorization_degree;
            $base['revision_degree'] = $report->reservation_degree;
        } elseif ($report instanceof AcademicSessionReport || $report instanceof InteractiveSessionReport) {
            $base['homework_degree'] = $report->homework_degree;
        } else {
            $base[$type === 'quran' ? 'memorization_degree' : 'homework_degree'] = null;
            if ($type === 'quran') {
                $base['revision_degree'] = null;
            }
        }

        return $base;
    }
}
