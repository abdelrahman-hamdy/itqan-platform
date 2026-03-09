<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveSessionReport;
use App\Models\StudentSessionReport;
use App\Services\Reports\TeacherStudentOverviewService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SessionReportListController extends Controller
{
    public function __construct(
        private TeacherStudentOverviewService $overviewService,
    ) {
        $this->middleware('auth');
    }

    /**
     * Display session reports with tab routing.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $tab = $request->input('tab', 'students');
        $user = Auth::user();

        if ($tab === 'sessions') {
            return $this->sessionReportsTab($request, $user);
        }

        return $this->studentOverviewTab($request, $user);
    }

    /**
     * Tab 1: Student overview with aggregate stats per entity.
     */
    private function studentOverviewTab(Request $request, $user): View
    {
        $type = $request->input('type');
        $entityId = $request->input('entity_id') ? (int) $request->input('entity_id') : null;
        $studentSearch = $request->input('student_search');

        $rows = $this->overviewService->getStudentOverviewForTeacher($user, $type, $entityId, $studentSearch);
        $entityOptions = $this->overviewService->buildEntityOptions($user);

        // Stats
        $totalStudents = $rows->count();
        $totalEntities = $rows->unique(fn ($r) => $r->entity_type.'_'.$r->entity_name)->count();
        $avgAttendance = $totalStudents > 0 ? round($rows->avg('attendance_rate')) : 0;

        // Paginate
        $page = $request->input('page', 1);
        $perPage = 15;
        $paginatedRows = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('teacher.session-reports.index', [
            'activeTab' => 'students',
            'paginatedRows' => $paginatedRows,
            'entityOptions' => $entityOptions,
            'totalStudents' => $totalStudents,
            'totalEntities' => $totalEntities,
            'avgAttendance' => $avgAttendance,
        ]);
    }

    /**
     * Tab 2: Session reports (enhanced with type/entity/student filters).
     */
    private function sessionReportsTab(Request $request, $user): View
    {
        $reportType = $request->input('report_type');
        $entityId = $request->input('entity_id') ? (int) $request->input('entity_id') : null;
        $studentSearch = $request->input('student_search');

        $quranReports = collect();
        $academicReports = collect();
        $interactiveReports = collect();

        // Only query relevant report types when a type filter is selected
        $shouldQueryQuran = $user->isQuranTeacher() && (! $reportType || $reportType === 'quran');
        $shouldQueryAcademic = $user->isAcademicTeacher() && (! $reportType || $reportType === 'academic');
        $shouldQueryInteractive = $user->isAcademicTeacher() && (! $reportType || $reportType === 'interactive');

        if ($shouldQueryQuran) {
            $quranQuery = StudentSessionReport::query()
                ->with(['student:id,first_name,last_name,name', 'session'])
                ->where('teacher_id', $user->id);

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

        if ($shouldQueryAcademic || $shouldQueryInteractive) {
            $profileId = $user->academicTeacherProfile?->id;

            if ($profileId) {
                if ($shouldQueryAcademic) {
                    $academicQuery = AcademicSessionReport::query()
                        ->with(['student:id,first_name,last_name,name', 'session'])
                        ->whereHas('session', fn ($q) => $q->where('academic_teacher_id', $profileId));

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
                        ->with(['student:id,first_name,last_name,name', 'session.course'])
                        ->whereHas('session.course', fn ($q) => $q->where('assigned_teacher_id', $profileId));

                    if ($entityId) {
                        $interactiveQuery->whereHas('session', fn ($q) => $q->where('interactive_course_id', $entityId));
                    }

                    if ($studentSearch) {
                        $interactiveQuery->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$studentSearch}%"));
                    }

                    $this->applyCommonFilters($interactiveQuery, $request);
                    $interactiveReports = $interactiveQuery->latest()->get();
                }
            }
        }

        $allReports = $quranReports
            ->merge($academicReports)
            ->merge($interactiveReports)
            ->sortByDesc('created_at');

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = 15;
        $total = $allReports->count();
        $paginatedReports = new LengthAwarePaginator(
            $allReports->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Stats
        $totalReports = $total;
        $presentCount = $allReports->where('attendance_status', AttendanceStatus::ATTENDED)->count();
        $absentCount = $allReports->where('attendance_status', AttendanceStatus::ABSENT)->count();
        $lateCount = $allReports->where('attendance_status', AttendanceStatus::LATE)->count();

        // Entity options for the cascading filter
        $entityOptions = $this->overviewService->buildEntityOptions($user);

        return view('teacher.session-reports.index', [
            'activeTab' => 'sessions',
            'paginatedReports' => $paginatedReports,
            'totalReports' => $totalReports,
            'presentCount' => $presentCount,
            'absentCount' => $absentCount,
            'lateCount' => $lateCount,
            'entityOptions' => $entityOptions,
        ]);
    }

    /**
     * Apply common filters to a report query.
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
    }
}
