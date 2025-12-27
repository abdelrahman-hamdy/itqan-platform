<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasDateRangeFilter;
use App\Models\QuranCircle;
use App\Models\User;
use App\Services\Reports\QuranReportService;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class GroupCircleReportController extends Controller
{
    use HasDateRangeFilter;

    protected QuranReportService $reportService;

    public function __construct(QuranReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display comprehensive report for group circle (all students)
     */
    public function show(Request $request, $subdomain, QuranCircle $circle)
    {
        // Authorization: Ensure teacher owns this circle
        if ($circle->quran_teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بعرض هذا التقرير');
        }

        // Generate report data
        $reportData = $this->reportService->getGroupCircleReport($circle);

        return view('teacher.group-circles.report', $reportData);
    }

    /**
     * Display detailed report for specific student in group circle
     */
    public function studentReport(Request $request, $subdomain, QuranCircle $circle, User $student)
    {
        // Authorization: Ensure teacher owns this circle
        if ($circle->quran_teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بعرض هذا التقرير');
        }

        // Ensure student is enrolled in this circle
        if (!$circle->students()->where('student_id', $student->id)->exists()) {
            abort(404, 'الطالب غير مسجل في هذه الحلقة');
        }

        // Get date range filter
        $dateRange = $this->getDateRangeFromRequest($request);

        // Generate student-specific report with date filter
        $reportData = $this->reportService->getStudentReportInGroupCircle($circle, $student, $dateRange);

        return view('reports.quran.circle-report', array_merge(
            $reportData,
            $this->getDateRangeViewData($request),
            [
                'layoutType' => 'teacher',
                'circle' => $circle,
                'circleType' => 'group',
            ]
        ));
    }
}
