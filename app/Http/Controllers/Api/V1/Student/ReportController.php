<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\PaginatesResults;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveSessionReport;
use App\Models\StudentSessionReport;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Student Session Report Controller
 *
 * Provides endpoints for students to view their session reports,
 * individual report details, and aggregate summary statistics
 * across all session types (Quran, Academic, Interactive).
 */
class ReportController extends Controller
{
    use ApiResponses, PaginatesResults;

    /**
     * Get paginated session reports for the authenticated student.
     *
     * Filters: type (quran, academic, interactive), date_from, date_to
     * Merges all report types, sorted by created_at desc.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $allReports = collect();

        // Quran reports
        if (! $type || $type === 'quran') {
            $quranReports = $this->getQuranReports($user->id, $dateFrom, $dateTo);
            $allReports = $allReports->merge($quranReports);
        }

        // Academic reports
        if (! $type || $type === 'academic') {
            $academicReports = $this->getAcademicReports($user->id, $dateFrom, $dateTo);
            $allReports = $allReports->merge($academicReports);
        }

        // Interactive reports
        if (! $type || $type === 'interactive') {
            $interactiveReports = $this->getInteractiveReports($user->id, $dateFrom, $dateTo);
            $allReports = $allReports->merge($interactiveReports);
        }

        // Sort by created_at desc
        $sorted = $allReports->sortByDesc('evaluated_at')->values();

        // Manual pagination
        $page = PaginationHelper::getPage($request);
        $perPage = PaginationHelper::getPerPage($request);
        $total = $sorted->count();
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values()->toArray();

        return $this->paginatedFromArray(
            $items,
            $total,
            $page,
            $perPage,
            __('Session reports retrieved successfully')
        );
    }

    /**
     * Get a single report by session type and session ID.
     */
    public function show(Request $request, string $type, string $sessionId): JsonResponse
    {
        $user = $request->user();

        $report = match ($type) {
            'quran' => $this->getQuranReportDetail($user->id, $sessionId),
            'academic' => $this->getAcademicReportDetail($user->id, $sessionId),
            'interactive' => $this->getInteractiveReportDetail($user->id, $sessionId),
            default => null,
        };

        if (! $report) {
            return $this->notFound(__('Report not found.'));
        }

        return $this->success($report, __('Session report retrieved successfully'));
    }

    /**
     * Return aggregate stats: total reports, average score, attendance rate, per-type breakdown.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $quranReports = StudentSessionReport::where('student_id', $user->id)
            ->whereNotNull('evaluated_at')
            ->get();

        $academicReports = AcademicSessionReport::where('student_id', $user->id)
            ->whereNotNull('evaluated_at')
            ->get();

        $interactiveReports = InteractiveSessionReport::where('student_id', $user->id)
            ->whereNotNull('evaluated_at')
            ->get();

        $allReports = collect()
            ->merge($quranReports)
            ->merge($academicReports)
            ->merge($interactiveReports);

        $totalReports = $allReports->count();

        // Average score across all evaluated reports
        $scores = $allReports->map(fn ($r) => $r->overall_performance)->filter()->values();
        $averageScore = $scores->isNotEmpty() ? round($scores->avg(), 1) : null;

        // Attendance rate
        $attendanceRate = $this->calculateAttendanceRate($allReports);

        return $this->success([
            'total_reports' => $totalReports,
            'average_score' => $averageScore,
            'attendance_rate' => $attendanceRate,
            'breakdown' => [
                'quran' => $this->buildTypeBreakdown($quranReports),
                'academic' => $this->buildTypeBreakdown($academicReports),
                'interactive' => $this->buildTypeBreakdown($interactiveReports),
            ],
        ], __('Report summary retrieved successfully'));
    }

    // ========================================
    // Private: Query Methods
    // ========================================

    /**
     * Get formatted Quran reports for a student.
     */
    private function getQuranReports(string $studentId, ?string $dateFrom, ?string $dateTo): Collection
    {
        $query = StudentSessionReport::where('student_id', $studentId)
            ->whereNotNull('evaluated_at')
            ->with(['session.quranTeacher']);

        $this->applyDateFilters($query, $dateFrom, $dateTo);

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($report) => $this->formatQuranReport($report));
    }

    /**
     * Get formatted Academic reports for a student.
     */
    private function getAcademicReports(string $studentId, ?string $dateFrom, ?string $dateTo): Collection
    {
        $query = AcademicSessionReport::where('student_id', $studentId)
            ->whereNotNull('evaluated_at')
            ->with(['session.academicTeacher.user']);

        $this->applyDateFilters($query, $dateFrom, $dateTo);

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($report) => $this->formatAcademicReport($report));
    }

    /**
     * Get formatted Interactive reports for a student.
     */
    private function getInteractiveReports(string $studentId, ?string $dateFrom, ?string $dateTo): Collection
    {
        $query = InteractiveSessionReport::where('student_id', $studentId)
            ->whereNotNull('evaluated_at')
            ->with(['session.course.assignedTeacher.user']);

        $this->applyDateFilters($query, $dateFrom, $dateTo);

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($report) => $this->formatInteractiveReport($report));
    }

    /**
     * Get a single Quran report with full detail.
     */
    private function getQuranReportDetail(string $studentId, int $sessionId): ?array
    {
        $report = StudentSessionReport::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->with(['session.quranTeacher'])
            ->first();

        if (! $report) {
            return null;
        }

        return $this->formatQuranReport($report);
    }

    /**
     * Get a single Academic report with full detail.
     */
    private function getAcademicReportDetail(string $studentId, int $sessionId): ?array
    {
        $report = AcademicSessionReport::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->with(['session.academicTeacher.user'])
            ->first();

        if (! $report) {
            return null;
        }

        return $this->formatAcademicReport($report);
    }

    /**
     * Get a single Interactive report with full detail.
     */
    private function getInteractiveReportDetail(string $studentId, int $sessionId): ?array
    {
        $report = InteractiveSessionReport::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->with(['session.course.assignedTeacher.user'])
            ->first();

        if (! $report) {
            return null;
        }

        return $this->formatInteractiveReport($report);
    }

    // ========================================
    // Private: Formatting Methods
    // ========================================

    /**
     * Format a Quran session report for the API response.
     */
    private function formatQuranReport(StudentSessionReport $report): array
    {
        $session = $report->session;
        $teacher = $session?->quranTeacher;

        return [
            'id' => $report->id,
            'type' => 'quran',
            'session' => [
                'id' => $session?->id,
                'title' => $session?->title ?? __('جلسة قرآنية'),
                'session_code' => $session?->session_code,
                'scheduled_at' => $session?->scheduled_at?->toISOString(),
                'session_type' => $session?->session_type,
            ],
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
            ] : null,
            'attendance' => [
                'status' => $this->resolveAttendanceStatusValue($report->attendance_status),
                'status_label' => $report->attendance_status_in_arabic,
                'duration_minutes' => $report->actual_attendance_minutes,
                'is_late' => $report->is_late,
                'late_minutes' => $report->late_minutes,
                'attendance_percentage' => $report->attendance_percentage,
                'enter_time' => $report->meeting_enter_time?->toISOString(),
                'leave_time' => $report->meeting_leave_time?->toISOString(),
            ],
            'performance' => [
                'overall_score' => $report->overall_performance,
                'performance_level' => $report->performance_level,
                'memorization_degree' => $report->new_memorization_degree,
                'revision_degree' => $report->reservation_degree,
            ],
            'notes' => $report->notes,
            'evaluated_at' => $report->evaluated_at?->toISOString(),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    /**
     * Format an Academic session report for the API response.
     */
    private function formatAcademicReport(AcademicSessionReport $report): array
    {
        $session = $report->session;
        $teacher = $session?->academicTeacher?->user;

        return [
            'id' => $report->id,
            'type' => 'academic',
            'session' => [
                'id' => $session?->id,
                'title' => $session?->title ?? __('جلسة أكاديمية'),
                'session_code' => $session?->session_code,
                'scheduled_at' => $session?->scheduled_at?->toISOString(),
            ],
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
            ] : null,
            'attendance' => [
                'status' => $this->resolveAttendanceStatusValue($report->attendance_status),
                'status_label' => $report->attendance_status_in_arabic,
                'duration_minutes' => $report->actual_attendance_minutes,
                'is_late' => $report->is_late,
                'late_minutes' => $report->late_minutes,
                'attendance_percentage' => $report->attendance_percentage,
                'enter_time' => $report->meeting_enter_time?->toISOString(),
                'leave_time' => $report->meeting_leave_time?->toISOString(),
            ],
            'performance' => [
                'overall_score' => $report->overall_performance,
                'performance_level' => $report->performance_level,
                'homework_degree' => $report->homework_degree,
            ],
            'notes' => $report->notes,
            'evaluated_at' => $report->evaluated_at?->toISOString(),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    /**
     * Format an Interactive session report for the API response.
     */
    private function formatInteractiveReport(InteractiveSessionReport $report): array
    {
        $session = $report->session;
        $teacher = $session?->course?->assignedTeacher?->user;

        return [
            'id' => $report->id,
            'type' => 'interactive',
            'session' => [
                'id' => $session?->id,
                'title' => $session?->title ?? $session?->course?->title ?? __('جلسة تفاعلية'),
                'session_code' => $session?->session_code,
                'scheduled_at' => $session?->scheduled_at?->toISOString(),
            ],
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
            ] : null,
            'attendance' => [
                'status' => $this->resolveAttendanceStatusValue($report->attendance_status),
                'status_label' => $report->attendance_status_in_arabic,
                'duration_minutes' => $report->actual_attendance_minutes,
                'is_late' => $report->is_late,
                'late_minutes' => $report->late_minutes,
                'attendance_percentage' => $report->attendance_percentage,
                'enter_time' => $report->meeting_enter_time?->toISOString(),
                'leave_time' => $report->meeting_leave_time?->toISOString(),
            ],
            'performance' => [
                'overall_score' => $report->overall_performance,
                'performance_level' => $report->performance_level,
                'homework_degree' => $report->homework_degree,
            ],
            'notes' => $report->notes,
            'evaluated_at' => $report->evaluated_at?->toISOString(),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    // ========================================
    // Private: Helper Methods
    // ========================================

    /**
     * Apply date range filters to a report query.
     */
    private function applyDateFilters($query, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    /**
     * Safely resolve attendance status to its string value.
     *
     * Handles both BackedEnum instances and raw string values.
     */
    private function resolveAttendanceStatusValue($status): ?string
    {
        if ($status === null) {
            return null;
        }

        if ($status instanceof BackedEnum) {
            return $status->value;
        }

        return (string) $status;
    }

    /**
     * Calculate attendance rate from a collection of reports.
     */
    private function calculateAttendanceRate(Collection $reports): float
    {
        $total = $reports->count();

        if ($total === 0) {
            return 0;
        }

        $attended = $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, AttendanceStatus::presentValues());
        })->count();

        return round(($attended / $total) * 100, 1);
    }

    /**
     * Build per-type breakdown stats for the summary endpoint.
     */
    private function buildTypeBreakdown(Collection $reports): array
    {
        $total = $reports->count();
        $scores = $reports->map(fn ($r) => $r->overall_performance)->filter()->values();

        return [
            'total_reports' => $total,
            'average_score' => $scores->isNotEmpty() ? round($scores->avg(), 1) : null,
            'attendance_rate' => $this->calculateAttendanceRate($reports),
        ];
    }
}
