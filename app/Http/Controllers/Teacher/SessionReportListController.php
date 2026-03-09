<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveSessionReport;
use App\Models\StudentSessionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SessionReportListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of session reports for the authenticated teacher.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();

        // Build unified collection from multiple report types
        $quranReports = collect();
        $academicReports = collect();
        $interactiveReports = collect();

        if ($user->isQuranTeacher()) {
            $quranQuery = StudentSessionReport::query()
                ->with(['student:id,first_name,last_name,name', 'session'])
                ->where('teacher_id', $user->id);

            $this->applyCommonFilters($quranQuery, $request);

            $quranReports = $quranQuery->latest()->get();
        }

        if ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;

            if ($profileId) {
                // Academic session reports
                $academicQuery = AcademicSessionReport::query()
                    ->with(['student:id,first_name,last_name,name', 'session'])
                    ->whereHas('session', fn ($q) => $q->where('academic_teacher_id', $profileId));

                $this->applyCommonFilters($academicQuery, $request);

                $academicReports = $academicQuery->latest()->get();

                // Interactive session reports
                $interactiveQuery = InteractiveSessionReport::query()
                    ->with(['student:id,first_name,last_name,name', 'session.course'])
                    ->whereHas('session.course', fn ($q) => $q->where('assigned_teacher_id', $profileId));

                $this->applyCommonFilters($interactiveQuery, $request);

                $interactiveReports = $interactiveQuery->latest()->get();
            }
        }

        // Merge and sort all reports
        $allReports = $quranReports
            ->merge($academicReports)
            ->merge($interactiveReports)
            ->sortByDesc('created_at');

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = 15;
        $total = $allReports->count();
        $paginatedReports = new \Illuminate\Pagination\LengthAwarePaginator(
            $allReports->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Stats
        $totalReports = $total;
        $presentCount = $allReports->where('attendance_status', \App\Enums\AttendanceStatus::ATTENDED)->count();
        $absentCount = $allReports->where('attendance_status', \App\Enums\AttendanceStatus::ABSENT)->count();
        $lateCount = $allReports->where('attendance_status', \App\Enums\AttendanceStatus::LATE)->count();

        return view('teacher.session-reports.index', compact(
            'paginatedReports',
            'totalReports',
            'presentCount',
            'absentCount',
            'lateCount',
        ));
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
