<?php

namespace App\Http\Controllers\Teacher;

use App\Contracts\QuranReportServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasDateRangeFilter;
use App\Models\QuranIndividualCircle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndividualCircleReportController extends Controller
{
    use HasDateRangeFilter;

    protected QuranReportServiceInterface $reportService;

    public function __construct(QuranReportServiceInterface $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display comprehensive report for individual circle
     */
    public function show(Request $request, $subdomain, QuranIndividualCircle $circle): View
    {
        $this->authorize('viewReport', $circle);

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
