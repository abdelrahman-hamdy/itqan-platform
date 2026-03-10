<?php

namespace App\Http\Controllers;

use App\Models\AcademicSessionReport;
use App\Models\InteractiveSessionReport;
use App\Models\StudentSessionReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionReportShowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a single session report detail page.
     *
     * @param  string  $type  quran|academic|interactive
     * @param  int  $id  Report ID
     */
    public function show(Request $request, $subdomain, string $type, int $id): View
    {
        $report = match ($type) {
            'quran' => StudentSessionReport::with([
                'student:id,first_name,last_name,name,avatar',
                'teacher:id,first_name,last_name,name',
                'session' => fn ($q) => $q->with(['individualCircle', 'circle']),
            ])->findOrFail($id),

            'academic' => AcademicSessionReport::with([
                'student:id,first_name,last_name,name,avatar',
                'teacher:id,first_name,last_name,name',
                'session' => fn ($q) => $q->with(['academicTeacher.user:id,first_name,last_name,name', 'academicIndividualLesson']),
            ])->findOrFail($id),

            'interactive' => InteractiveSessionReport::with([
                'student:id,first_name,last_name,name,avatar',
                'teacher:id,first_name,last_name,name',
                'session' => fn ($q) => $q->with(['course.assignedTeacher.user:id,first_name,last_name,name']),
            ])->findOrFail($id),

            default => abort(404),
        };

        // Determine layout from route prefix
        $layoutType = str_starts_with($request->route()->getName(), 'manage.') ? 'supervisor' : 'teacher';

        // Determine back route
        $backRoute = $layoutType === 'supervisor'
            ? route('manage.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'sessions'])
            : route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'sessions']);

        return view('reports.session-report-show', [
            'report' => $report,
            'reportType' => $type,
            'layoutType' => $layoutType,
            'backRoute' => $backRoute,
        ]);
    }
}
