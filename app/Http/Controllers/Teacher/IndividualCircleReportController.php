<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasDateRangeFilter;
use App\Models\QuranIndividualCircle;
use App\Services\Reports\QuranReportService;
use Illuminate\Http\Request;

class IndividualCircleReportController extends Controller
{
    use HasDateRangeFilter;

    protected QuranReportService $reportService;

    public function __construct(QuranReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display comprehensive report for individual circle
     */
    public function show(Request $request, $subdomain, QuranIndividualCircle $circle)
    {
        // Authorization: Ensure teacher owns this circle
        if ($circle->quran_teacher_id !== auth()->id()) {
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
                'layoutType' => 'teacher',
                'circleType' => 'individual',
            ]
        ));
    }
}
