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

        // Get date range filter
        $dateRange = $this->getDateRangeFromRequest($request);

        // Generate report data with date filter
        $reportData = $this->reportService->getIndividualCircleReport($circle, $dateRange);

        return view('teacher.circle-report', array_merge($reportData, [
            'circleType' => 'individual',
            'filterPeriod' => $request->get('period', 'all'),
            'customStartDate' => $request->get('start_date'),
            'customEndDate' => $request->get('end_date'),
        ]));
    }

    /**
     * Get date range from request parameters
     */
    protected function getDateRangeFromRequest(Request $request): ?array
    {
        $period = $request->get('period', 'all');

        switch ($period) {
            case 'this_month':
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth(),
                ];

            case 'last_3_months':
                return [
                    'start' => now()->subMonths(3)->startOfMonth(),
                    'end' => now()->endOfMonth(),
                ];

            case 'custom':
                $startDate = $request->get('start_date');
                $endDate = $request->get('end_date');

                if ($startDate && $endDate) {
                    return [
                        'start' => \Carbon\Carbon::parse($startDate)->startOfDay(),
                        'end' => \Carbon\Carbon::parse($endDate)->endOfDay(),
                    ];
                }
                return null;

            case 'all':
            default:
                return null; // No filtering
        }
    }
}
