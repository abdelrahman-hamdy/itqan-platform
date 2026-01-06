<?php

namespace App\Services;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;

/**
 * Quran Circle Report Service (Facade)
 *
 * This service acts as a facade coordinating between:
 * - CircleDataFetcherService: Data fetching and queries
 * - CircleReportFormatterService: Report formatting and presentation
 *
 * Maintains backward compatibility with existing code while delegating
 * to smaller, focused services.
 *
 * IMPORTANT: All progress tracking is pages-only (NOT verses)
 *
 * NOTE: QuranProgress model has been removed. Progress is now calculated
 * dynamically from session reports and circle model fields.
 */
class QuranCircleReportService
{
    public function __construct(
        protected CircleReportFormatterService $formatter
    ) {}

    /**
     * Generate comprehensive report for individual circle
     *
     * @param  array|null  $dateRange  Optional date range filter ['start' => Carbon, 'end' => Carbon]
     */
    public function getIndividualCircleReport(QuranIndividualCircle $circle, ?array $dateRange = null): array
    {
        return $this->formatter->getIndividualCircleReport($circle, $dateRange);
    }

    /**
     * Generate comprehensive report for group circle (all students)
     */
    public function getGroupCircleReport(QuranCircle $circle): array
    {
        return $this->formatter->getGroupCircleReport($circle);
    }

    /**
     * Generate report for specific student in group circle
     *
     * @param  array|null  $dateRange  Optional date range filter ['start' => Carbon, 'end' => Carbon]
     */
    public function getStudentReportInGroupCircle(QuranCircle $circle, User $student, ?array $dateRange = null): array
    {
        return $this->formatter->getStudentReportInGroupCircle($circle, $student, $dateRange);
    }
}
