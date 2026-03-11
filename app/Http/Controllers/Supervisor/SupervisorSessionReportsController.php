<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\User;
use App\Services\Reports\SessionReportsQueryService;
use App\Services\Reports\StudentOverviewService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SupervisorSessionReportsController extends BaseSupervisorWebController
{
    public function __construct(
        private StudentOverviewService $overviewService,
        private SessionReportsQueryService $sessionReportsService,
    ) {
        parent::__construct();
    }

    /**
     * Display session reports with tab routing (mirrors teacher reports page).
     */
    public function index(Request $request, $subdomain = null): View
    {
        $tab = $request->input('tab', 'students');

        if ($tab === 'sessions') {
            return $this->sessionReportsTab($request);
        }

        return $this->studentOverviewTab($request);
    }

    /**
     * Tab 1: Student overview with aggregate stats per entity.
     */
    private function studentOverviewTab(Request $request): View
    {
        $type = $request->input('type');
        $entityId = $request->input('entity_id') ? (int) $request->input('entity_id') : null;
        $studentSearch = $request->input('student_search');

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $rows = $this->overviewService->getStudentOverview(
            $quranTeacherIds,
            $academicProfileIds,
            $type,
            $entityId,
            $studentSearch,
            'manage',
        );

        $entityOptions = $this->overviewService->buildEntityOptions($quranTeacherIds, $academicProfileIds);
        $teacherOptions = $this->buildTeacherOptions($quranTeacherIds, $academicProfileIds);

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

        return view('supervisor.session-reports.index', [
            'activeTab' => 'students',
            'paginatedRows' => $paginatedRows,
            'entityOptions' => $entityOptions,
            'teacherOptions' => $teacherOptions,
            'totalStudents' => $totalStudents,
            'totalEntities' => $totalEntities,
            'avgAttendance' => $avgAttendance,
        ]);
    }

    /**
     * Tab 2: Session reports with filters and stats.
     */
    private function sessionReportsTab(Request $request): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

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
        $teacherOptions = $this->buildTeacherOptions($quranTeacherIds, $academicProfileIds);

        return view('supervisor.session-reports.index', [
            'activeTab' => 'sessions',
            'paginatedReports' => $paginatedReports,
            'totalReports' => $result['totalReports'],
            'presentCount' => $result['presentCount'],
            'absentCount' => $result['absentCount'],
            'lateCount' => $result['lateCount'],
            'entityOptions' => $entityOptions,
            'teacherOptions' => $teacherOptions,
        ]);
    }

    /**
     * Build teacher options for the filter dropdown.
     *
     * @return array<int, string>  [user_id => name]
     */
    private function buildTeacherOptions(array $quranTeacherIds, array $academicProfileIds): array
    {
        $allTeacherUserIds = collect($quranTeacherIds);

        if (! empty($academicProfileIds)) {
            $academicUserIds = \App\Models\AcademicTeacherProfile::whereIn('id', $academicProfileIds)
                ->pluck('user_id');
            $allTeacherUserIds = $allTeacherUserIds->merge($academicUserIds);
        }

        $allTeacherUserIds = $allTeacherUserIds->unique()->filter()->values();

        if ($allTeacherUserIds->isEmpty()) {
            return [];
        }

        return User::whereIn('id', $allTeacherUserIds)
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name ?? $u->first_name.' '.$u->last_name])
            ->toArray();
    }
}
