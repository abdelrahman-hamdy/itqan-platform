<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Services\QuranCircleReportService;
use Illuminate\Http\Request;

class CircleReportController extends Controller
{
    protected QuranCircleReportService $reportService;

    public function __construct(QuranCircleReportService $reportService)
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

        // Generate report data
        $reportData = $this->reportService->getIndividualCircleReport($circle);

        return view('student.circle-report', array_merge($reportData, [
            'circleType' => 'individual',
        ]));
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

        // Generate student-specific report
        $reportData = $this->reportService->getStudentReportInGroupCircle($circle, auth()->user());
        $reportData['circle'] = $circle;
        $reportData['circleType'] = 'group';

        return view('student.circle-report', $reportData);
    }
}
