<?php

namespace App\Observers;

use App\Models\AcademicSessionAttendance;

/**
 * Academic Session Attendance Observer
 *
 * NOTE: AcademicProgress model has been removed. Progress is now calculated
 * dynamically from session reports using the AcademicReportService.
 *
 * This observer is kept as a placeholder for potential future attendance-level
 * lifecycle hooks (e.g., notifications, analytics events).
 */
class AcademicSessionAttendanceObserver
{
    /**
     * Handle the AcademicSessionAttendance "created" event.
     */
    public function created(AcademicSessionAttendance $attendance): void
    {
        // Progress tracking has been moved to report-based calculations
        // No action needed here
    }

    /**
     * Handle the AcademicSessionAttendance "updated" event.
     */
    public function updated(AcademicSessionAttendance $attendance): void
    {
        // Progress tracking has been moved to report-based calculations
        // No action needed here
    }
}
