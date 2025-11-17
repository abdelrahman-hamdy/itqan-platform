<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuranIndividualCircle;
use App\Services\QuranCircleReportService;
use Illuminate\Http\Request;

class IndividualCircleReportController extends Controller
{
    protected QuranCircleReportService $reportService;

    public function __construct(QuranCircleReportService $reportService)
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

        // Generate report data
        $reportData = $this->reportService->getIndividualCircleReport($circle);

        return view('teacher.individual-circles.report', $reportData);
    }
}
