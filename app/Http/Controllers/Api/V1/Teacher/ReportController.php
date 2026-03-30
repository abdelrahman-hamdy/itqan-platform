<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Contracts\QuranReportServiceInterface;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponses;

    /**
     * Get report data for a Quran individual circle.
     * Returns subscription-like data for the mobile report screen.
     */
    public function quranIndividualReport(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranIndividualCircle::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->with(['student', 'subscription', 'sessions'])
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $subscription = $circle->subscription;

        // Calculate session statistics
        $allSessions = $circle->sessions;
        $totalSessions = $subscription?->sessions_count ?? $allSessions->count();
        $completedSessions = $allSessions->where('status', SessionStatus::COMPLETED)->count();
        $remainingSessions = max(0, $totalSessions - $completedSessions);

        $subscriptionData = [
            'id' => $circle->id,
            'type' => 'quran',
            'subscription_code' => $subscription?->subscription_code,
            'title' => $circle->name,
            'status' => $subscription?->status->value ?? 'active',
            'status_label' => $subscription?->status->label ?? 'نشط',
            'start_date' => ($subscription?->starts_at ?? $subscription?->start_date)?->toDateString(),
            'end_date' => ($subscription?->ends_at ?? $subscription?->end_date)?->toDateString(),
            'auto_renew' => $subscription?->auto_renew ?? false,
            'price' => (float) ($subscription?->final_price ?? $subscription?->monthly_price ?? 0),
            'currency' => $subscription?->currency ?? getCurrencyCode(null, $subscription?->academy),
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            ],
            'student' => $circle->student ? [
                'id' => $circle->student->id,
                'name' => $circle->student->name,
                'avatar' => $circle->student->avatar
                    ? asset('storage/'.$circle->student->avatar)
                    : null,
            ] : null,
            'sessions' => [
                'total' => $totalSessions,
                'used' => $completedSessions,
                'remaining' => $remainingSessions,
            ],
            'created_at' => $circle->created_at->toISOString(),
            'quran_details' => [
                'subscription_type' => 'individual',
                'memorization_level' => $subscription?->memorization_level,
            ],
        ];

        return $this->success([
            'subscription' => $subscriptionData,
        ], __('Report retrieved successfully'));
    }

    /**
     * Get sessions for a Quran individual circle report.
     */
    public function quranIndividualSessions(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranIndividualCircle::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $sessions = $circle->sessions()
            ->with(['reports'])
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(function ($s) {
                $report = $s->reports?->first();

                return [
                    'id' => $s->id,
                    'type' => 'quran',
                    'title' => $s->title,
                    'status' => $s->status->value ?? $s->status,
                    'scheduled_at' => $s->scheduled_at?->toISOString(),
                    'duration_minutes' => $s->duration_minutes ?? 60,
                    'attendance_status' => $s->attendance_status ?? null,
                    'memorization_degree' => $report?->new_memorization_degree,
                    'revision_degree' => $report?->reservation_degree,
                ];
            })
            ->toArray();

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get report data for a Quran group circle.
     */
    public function quranGroupReport(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->with(['sessions', 'students'])
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $allSessions = $circle->sessions;
        $completedSessions = $allSessions->where('status', SessionStatus::COMPLETED)->count();
        $totalSessions = $allSessions->count();

        $subscriptionData = [
            'id' => $circle->id,
            'type' => 'quran_group',
            'title' => $circle->name,
            'status' => $circle->status ?? 'active',
            'status_label' => 'نشط',
            'start_date' => $circle->created_at?->toDateString(),
            'end_date' => null,
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            ],
            'sessions' => [
                'total' => $totalSessions,
                'used' => $completedSessions,
                'remaining' => max(0, $totalSessions - $completedSessions),
            ],
            'created_at' => $circle->created_at->toISOString(),
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
                'current_students' => $circle->students?->count() ?? 0,
                'max_students' => $circle->max_students,
            ],
        ];

        return $this->success([
            'subscription' => $subscriptionData,
        ], __('Report retrieved successfully'));
    }

    /**
     * Get sessions for a Quran group circle report.
     */
    public function quranGroupSessions(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $sessions = $circle->sessions()
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'type' => 'quran',
                'title' => $s->title,
                'status' => $s->status->value ?? $s->status,
                'scheduled_at' => $s->scheduled_at?->toISOString(),
                'duration_minutes' => $s->duration_minutes ?? 60,
            ])
            ->toArray();

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get full report for a Quran individual circle using QuranReportService.
     * Returns the same format as the student SubscriptionController::report().
     */
    public function quranIndividualFullReport(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranIndividualCircle::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $dateRange = $this->parseDateRange($request);

        $reportService = app(QuranReportServiceInterface::class);
        $reportData = $reportService->getIndividualCircleReport($circle, $dateRange);

        return $this->formatReportResponse($reportData);
    }

    /**
     * Get per-student report summaries for a Quran group circle.
     * Returns a list of students with their key metrics.
     */
    public function quranGroupStudentsReportSummary(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $user->id)
            ->with('students')
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $dateRange = $this->parseDateRange($request);
        $reportService = app(QuranReportServiceInterface::class);

        $studentsReports = [];

        foreach ($circle->students as $student) {
            $reportData = $reportService->getStudentReportInGroupCircle($circle, $student, $dateRange);

            $attendance = $reportData['attendance'];
            $performance = $reportData['performance'];

            $studentsReports[] = [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'avatar' => $student->avatar ? asset('storage/'.$student->avatar) : null,
                ],
                'summary' => [
                    'attendance_rate' => $attendance->attendanceRate,
                    'memorization_score' => $performance->averageMemorization ?? 0,
                    'recitation_score' => $performance->averageReservation ?? 0,
                    'overall_performance' => $performance->averageOverall,
                ],
            ];
        }

        return $this->success([
            'students' => $studentsReports,
            'total' => count($studentsReports),
        ], __('Students reports retrieved successfully'));
    }

    /**
     * Get full report for a specific student in a Quran group circle.
     */
    public function quranGroupStudentReport(Request $request, int $circleId, int $studentId): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranCircle::where('id', $circleId)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $student = User::find($studentId);

        if (! $student || ! $circle->students()->where('users.id', $student->id)->exists()) {
            return $this->notFound(__('Student not found in this circle.'));
        }

        $dateRange = $this->parseDateRange($request);

        $reportService = app(QuranReportServiceInterface::class);
        $reportData = $reportService->getStudentReportInGroupCircle($circle, $student, $dateRange);

        return $this->formatReportResponse($reportData);
    }

    /**
     * Parse optional date range from request.
     */
    private function parseDateRange(Request $request): ?array
    {
        if ($request->has('start_date') && $request->has('end_date')) {
            return [
                'start' => Carbon::parse($request->get('start_date')),
                'end' => Carbon::parse($request->get('end_date')),
            ];
        }

        return null;
    }

    /**
     * Format report data DTOs into the standard API response format.
     */
    private function formatReportResponse(array $reportData): JsonResponse
    {
        $attendance = $reportData['attendance'];
        $performance = $reportData['performance'];
        $progress = $reportData['progress'];

        return $this->success([
            'report' => [
                'attendance' => $attendance->toArray(),
                'performance' => [
                    'memorization_score' => $performance->averageMemorization ?? 0,
                    'recitation_score' => $performance->averageReservation ?? 0,
                    'homework_score' => 0,
                    'overall_performance' => $performance->averageOverall,
                    'sessions_evaluated' => $performance->totalEvaluated,
                ],
                'progress' => [
                    'pages_memorized' => $progress['pages_memorized'] ?? 0,
                    'pages_reviewed' => $progress['pages_reviewed'] ?? 0,
                    'progress_rate' => $progress['progress_percentage'] ?? 0,
                    'sessions_evaluated' => $progress['sessions_evaluated'] ?? 0,
                ],
            ],
        ], __('Report retrieved successfully'));
    }
}
