<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuranCircle;
use App\Models\User;
use App\Services\QuranCircleReportService;
use Illuminate\Http\Request;

class GroupCircleReportController extends Controller
{
    protected QuranCircleReportService $reportService;

    public function __construct(QuranCircleReportService $reportService)
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
        $reportData['circle'] = $circle;
        $reportData['circleType'] = 'group';
        $reportData['filterPeriod'] = $request->get('period', 'all');
        $reportData['customStartDate'] = $request->get('start_date');
        $reportData['customEndDate'] = $request->get('end_date');

        return view('teacher.circle-report', $reportData);
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
