<?php

namespace App\Services\Reports;

use App\DTOs\Reports\AttendanceDTO;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Base Report Service
 *
 * Provides shared logic and utility methods for all report services.
 * Child services (Quran, Academic, Interactive) extend this class.
 */
abstract class BaseReportService
{
    /**
     * Calculate attendance statistics from session reports
     *
     * Uses standard counting: attended + late = total attendance
     * For points-based calculation (late=0.5), use calculatePointsBasedAttendanceRate()
     *
     * @param  Collection  $reports  Collection of session reports
     * @param  int  $totalSessions  Total number of sessions
     */
    protected function calculateAttendanceFromReports(Collection $reports, int $totalSessions): AttendanceDTO
    {
        $attended = $reports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::ATTENDED->value;
        })->count();

        $late = $reports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::LATE->value;
        })->count();

        $absent = $reports->filter(function ($report) {
            $status = $this->normalizeAttendanceStatus($report->attendance_status ?? '');

            return $status === AttendanceStatus::ABSENT->value;
        })->count();

        // Late students are counted as attended for attendance rate
        $totalAttended = $attended + $late;
        $attendanceRate = $totalSessions > 0
            ? round(($totalAttended / $totalSessions) * 100, 2)
            : 0;

        $avgDuration = $reports->avg('actual_attendance_minutes') ?? 0;

        return new AttendanceDTO(
            totalSessions: $totalSessions,
            attended: $attended,
            absent: $absent,
            late: $late,
            attendanceRate: $attendanceRate,
            averageDurationMinutes: (int) round($avgDuration)
        );
    }

    /**
     * Get date range from period string
     *
     * @param  string|null  $period  Period identifier (this_month, last_3_months, custom, all)
     * @param  array|null  $customDates  Custom date range ['start' => Carbon, 'end' => Carbon]
     * @return array|null Date range array or null for 'all'
     */
    protected function getDateRangeFromPeriod(?string $period, ?array $customDates = null): ?array
    {
        return match ($period) {
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'last_3_months' => [
                'start' => now()->subMonths(3)->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'custom' => $customDates,
            default => null,
        };
    }

    /**
     * Filter sessions by date range
     *
     * @param  Collection  $sessions  Sessions collection
     * @param  array|null  $dateRange  Date range ['start' => Carbon, 'end' => Carbon]
     * @return Collection Filtered sessions
     */
    protected function filterSessionsByDateRange(Collection $sessions, ?array $dateRange): Collection
    {
        if (! $dateRange || ! isset($dateRange['start']) || ! isset($dateRange['end'])) {
            return $sessions;
        }

        return $sessions->filter(function ($session) use ($dateRange) {
            $sessionDate = $session->scheduled_at ?? $session->scheduled_date;

            if (! $sessionDate) {
                return false;
            }

            $sessionDate = Carbon::parse($sessionDate);

            return $sessionDate->between($dateRange['start'], $dateRange['end']);
        });
    }

    /**
     * Calculate points-based attendance rate
     *
     * Quran circles use a points system: attended=1, late=0.5, absent=0
     *
     * @param  Collection  $reports  Session reports
     * @return float Points-based attendance rate percentage
     */
    protected function calculatePointsBasedAttendanceRate(Collection $reports): float
    {
        if ($reports->isEmpty()) {
            return 0.0;
        }

        $points = $reports->sum(function ($report) {
            $status = is_object($report->attendance_status)
                ? $report->attendance_status->value
                : $report->attendance_status;

            return match ($status) {
                AttendanceStatus::ATTENDED->value => 1,
                AttendanceStatus::LATE->value => 0.5,
                default => 0,
            };
        });

        $maxPoints = $reports->count();

        return $maxPoints > 0 ? round(($points / $maxPoints) * 100, 2) : 0.0;
    }

    /**
     * Get normalized attendance status
     *
     * Handles both enum and string attendance statuses
     *
     * @param  mixed  $status  Attendance status (enum or string)
     * @return string Normalized status string
     */
    protected function normalizeAttendanceStatus($status): string
    {
        // Handle null/empty
        if (empty($status)) {
            return '';
        }

        // Handle backed enums (have ->value property)
        if ($status instanceof \BackedEnum) {
            return (string) $status->value;
        }

        // Handle unit enums (have ->name property)
        if ($status instanceof \UnitEnum) {
            return $status->name;
        }

        // Handle regular strings
        return (string) $status;
    }

    /**
     * Build breadcrumb array for views
     *
     * @param  array  $items  Breadcrumb items [['label' => '', 'url' => ''], ...]
     * @return array Formatted breadcrumbs
     */
    protected function buildBreadcrumbs(array $items): array
    {
        return $items;
    }

    /**
     * Get current academy subdomain
     *
     * @return string Subdomain
     */
    protected function getAcademySubdomain(): string
    {
        return auth()->user()->academy->subdomain ?? 'itqan-academy';
    }
}
