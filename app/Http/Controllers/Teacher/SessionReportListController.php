<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Reports\SessionReportsQueryService;
use App\Services\Reports\StudentOverviewService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SessionReportListController extends Controller
{
    public function __construct(
        private StudentOverviewService $overviewService,
        private SessionReportsQueryService $sessionReportsService,
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

        [$quranTeacherIds, $academicProfileIds] = $this->overviewService->forTeacher($user);

        $rows = $this->overviewService->getStudentOverview(
            $quranTeacherIds,
            $academicProfileIds,
            $type,
            $entityId,
            $studentSearch,
            'teacher',
        );

        $entityOptions = $this->overviewService->buildEntityOptions($quranTeacherIds, $academicProfileIds);

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
        [$quranTeacherIds, $academicProfileIds] = $this->overviewService->forTeacher($user);

        $result = $this->sessionReportsService->getSessionReports(
            $quranTeacherIds,
            $academicProfileIds,
            $request,
        );

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = 15;
        $allReports = $result['reports'];
        $paginatedReports = new LengthAwarePaginator(
            $allReports->forPage($page, $perPage)->values(),
            $allReports->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Entity options for the cascading filter
        $entityOptions = $this->overviewService->buildEntityOptions($quranTeacherIds, $academicProfileIds);

        return view('teacher.session-reports.index', [
            'activeTab' => 'sessions',
            'paginatedReports' => $paginatedReports,
            'totalReports' => $result['totalReports'],
            'presentCount' => $result['presentCount'],
            'absentCount' => $result['absentCount'],
            'lateCount' => $result['lateCount'],
            'entityOptions' => $entityOptions,
        ]);
    }
}
