<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasDateRangeFilter;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Services\Reports\QuranReportService;
use Illuminate\Http\Request;

class CircleReportController extends Controller
{
    use HasDateRangeFilter;

    protected QuranReportService $reportService;

    public function __construct(QuranReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display student's own report for individual circle
     */
    public function showIndividual(Request $request, $subdomain, QuranIndividualCircle $circle)
    {
        // Authorization: Ensure student owns this circle
        if ($circle->student_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بعرض هذا التقرير');
        }

        // Get date range filter
        $dateRange = $this->getDateRangeFromRequest($request);

        // Generate report data with date filter
        $reportData = $this->reportService->getIndividualCircleReport($circle, $dateRange);

        return view('reports.quran.circle-report', array_merge(
            $reportData,
            $this->getDateRangeViewData($request),
            [
                'layoutType' => 'student',
                'circleType' => 'individual',
            ]
        ));
    }

    /**
     * Display student's own report for group circle
     */
    public function showGroup(Request $request, $subdomain, QuranCircle $circle)
    {
        // Authorization: Ensure student is enrolled in this circle
        if (!$circle->students()->where('student_id', auth()->id())->exists()) {
            abort(403, 'غير مصرح لك بعرض هذا التقرير');
        }

        // Get date range filter
        $dateRange = $this->getDateRangeFromRequest($request);

        // Generate student-specific report with date filter
        $reportData = $this->reportService->getStudentReportInGroupCircle($circle, auth()->user(), $dateRange);

        return view('reports.quran.circle-report', array_merge(
            $reportData,
            $this->getDateRangeViewData($request),
            [
                'layoutType' => 'student',
                'circle' => $circle,
                'circleType' => 'group',
            ]
        ));
    }
}
