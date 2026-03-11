<?php

namespace App\Services\Reports;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveSessionReport;
use App\Models\StudentSessionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SessionReportsQueryService
{
    /**
     * Get session reports across all types, scoped by teacher ID arrays.
     *
     * @param  int[]  $quranTeacherIds
     * @param  int[]  $academicProfileIds
     * @return array{reports: Collection, totalReports: int, presentCount: int, absentCount: int, lateCount: int}
     */
    public function getSessionReports(
        array $quranTeacherIds,
        array $academicProfileIds,
        Request $request,
    ): array {
        $reportType = $request->input('report_type');
        $entityId = $request->input('entity_id') ? (int) $request->input('entity_id') : null;
        $studentSearch = $request->input('student_search');

        $quranReports = collect();
        $academicReports = collect();
        $interactiveReports = collect();

        $shouldQueryQuran = ! empty($quranTeacherIds) && (! $reportType || $reportType === 'quran');
        $shouldQueryAcademic = ! empty($academicProfileIds) && (! $reportType || $reportType === 'academic');
        $shouldQueryInteractive = ! empty($academicProfileIds) && (! $reportType || $reportType === 'interactive');

        if ($shouldQueryQuran) {
            $quranQuery = StudentSessionReport::query()
                ->with(['student:id,first_name,last_name,name', 'session.quranTeacher:id,first_name,last_name,name'])
                ->whereIn('teacher_id', $quranTeacherIds);

            if ($entityId) {
                $quranQuery->whereHas('session', fn ($q) => $q->where('individual_circle_id', $entityId)
                    ->orWhere('circle_id', $entityId));
            }

            if ($studentSearch) {
                $quranQuery->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$studentSearch}%"));
            }

            $this->applyCommonFilters($quranQuery, $request);
            $quranReports = $quranQuery->latest()->get();
        }

        if ($shouldQueryAcademic) {
            $academicQuery = AcademicSessionReport::query()
                ->with(['student:id,first_name,last_name,name', 'session.academicTeacher.user:id,first_name,last_name,name'])
                ->whereHas('session', fn ($q) => $q->whereIn('academic_teacher_id', $academicProfileIds));

            if ($entityId) {
                $academicQuery->whereHas('session', fn ($q) => $q->where('academic_individual_lesson_id', $entityId));
            }

            if ($studentSearch) {
                $academicQuery->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$studentSearch}%"));
            }

            $this->applyCommonFilters($academicQuery, $request);
            $academicReports = $academicQuery->latest()->get();
        }

        if ($shouldQueryInteractive) {
            $interactiveQuery = InteractiveSessionReport::query()
                ->with(['student:id,first_name,last_name,name', 'session.course.assignedTeacher.user:id,first_name,last_name,name'])
                ->whereHas('session.course', fn ($q) => $q->whereIn('assigned_teacher_id', $academicProfileIds));

            if ($entityId) {
                $interactiveQuery->whereHas('session', fn ($q) => $q->where('interactive_course_id', $entityId));
            }

            if ($studentSearch) {
                $interactiveQuery->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$studentSearch}%"));
            }

            $this->applyCommonFilters($interactiveQuery, $request);
            $interactiveReports = $interactiveQuery->latest()->get();
        }

        $allReports = $quranReports
            ->merge($academicReports)
            ->merge($interactiveReports)
            ->sortByDesc('created_at');

        return [
            'reports' => $allReports,
            'totalReports' => $allReports->count(),
            'presentCount' => $allReports->where('attendance_status', AttendanceStatus::ATTENDED)->count(),
            'absentCount' => $allReports->where('attendance_status', AttendanceStatus::ABSENT)->count(),
            'lateCount' => $allReports->where('attendance_status', AttendanceStatus::LATE)->count(),
        ];
    }

    /**
     * Get the teacher name from a report model.
     */
    public static function getTeacherName(object $report): string
    {
        if ($report instanceof StudentSessionReport) {
            return $report->session?->quranTeacher?->name ?? '';
        }

        if ($report instanceof AcademicSessionReport) {
            return $report->session?->academicTeacher?->user?->name ?? '';
        }

        if ($report instanceof InteractiveSessionReport) {
            return $report->session?->course?->assignedTeacher?->user?->name ?? '';
        }

        return '';
    }

    /**
     * Apply common filters (date range, attendance status) to a report query.
     */
    private function applyCommonFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('attendance_status')) {
            $query->where('attendance_status', $request->attendance_status);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', (int) $request->teacher_id);
        }
    }
}
