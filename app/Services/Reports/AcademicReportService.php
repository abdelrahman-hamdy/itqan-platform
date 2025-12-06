<?php

namespace App\Services\Reports;

use App\DTOs\Reports\{AttendanceDTO, PerformanceDTO, ProgressDTO, StatDTO};
use App\Models\AcademicSubscription;
use Illuminate\Support\Collection;

/**
 * Academic Report Service
 *
 * Handles report generation for academic subscriptions including
 * attendance, performance, and progress tracking.
 */
class AcademicReportService extends BaseReportService
{
    /**
     * Get subscription report
     *
     * @param AcademicSubscription $subscription
     * @param array|null $dateRange
     * @return array Report data with DTOs
     */
    public function getSubscriptionReport(AcademicSubscription $subscription, ?array $dateRange = null): array
    {
        $sessions = $subscription->sessions()->with('studentReports')->get();

        if ($dateRange) {
            $sessions = $this->filterSessionsByDateRange($sessions, $dateRange);
        }

        $reports = $sessions->flatMap->studentReports;

        return [
            'subscription' => $subscription,
            'student' => $subscription->student,
            'attendance' => $this->calculateAttendanceFromReports($reports, $sessions->count()),
            'performance' => $this->calculatePerformance($reports),
            'progress' => $this->calculateProgress($subscription, $sessions),
            'sessions' => $sessions,
            'statsCards' => $this->generateStatsCards($subscription, $sessions, $reports),
        ];
    }

    /**
     * Calculate performance from reports
     *
     * @param Collection $reports
     * @return PerformanceDTO
     */
    protected function calculatePerformance(Collection $reports): PerformanceDTO
    {
        $avgHomework = $reports->whereNotNull('homework_degree')->avg('homework_degree') ?? 0;
        $totalEvaluated = $reports->whereNotNull('homework_degree')->count();

        return PerformanceDTO::fromAcademicData([
            'average_overall_performance' => $avgHomework,
            'average_homework_degree' => $avgHomework,
            'sessions_evaluated' => $totalEvaluated,
        ]);
    }

    /**
     * Calculate progress
     *
     * @param AcademicSubscription $subscription
     * @param Collection $sessions
     * @return array Progress data
     */
    protected function calculateProgress(AcademicSubscription $subscription, Collection $sessions): array
    {
        $totalPlanned = $subscription->total_sessions ?? 0;
        $completed = $sessions->filter(function ($session) {
            $status = $this->normalizeAttendanceStatus($session->status ?? '');
            return $status === 'completed';
        })->count();

        $completionRate = $totalPlanned > 0 ? round(($completed / $totalPlanned) * 100, 2) : 0;

        return [
            'sessions_completed' => $completed,
            'total_sessions' => $totalPlanned,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * Generate stats cards for overview
     *
     * @param AcademicSubscription $subscription
     * @param Collection $sessions
     * @param Collection $reports
     * @return array Array of StatDTO
     */
    protected function generateStatsCards(AcademicSubscription $subscription, Collection $sessions, Collection $reports): array
    {
        $attendance = $this->calculateAttendanceFromReports($reports, $sessions->count());
        $performance = $this->calculatePerformance($reports);
        $progress = $this->calculateProgress($subscription, $sessions);

        return [
            new StatDTO(
                label: 'نسبة حضوري',
                value: number_format($attendance->attendanceRate, 0) . '%',
                color: $attendance->getColorClass(),
                icon: 'ri-user-star-line'
            ),
            new StatDTO(
                label: 'الجلسات المكتملة',
                value: $progress['sessions_completed'],
                color: 'blue',
                icon: 'ri-checkbox-circle-line'
            ),
            new StatDTO(
                label: 'متوسط أدائي',
                value: number_format($performance->averageOverall, 1) . '/10',
                color: $performance->getColorClass(),
                icon: 'ri-star-line'
            ),
            new StatDTO(
                label: 'نسبة التقدم',
                value: number_format($progress['completion_rate'], 0) . '%',
                color: 'yellow',
                icon: 'ri-pie-chart-line'
            ),
        ];
    }
}
