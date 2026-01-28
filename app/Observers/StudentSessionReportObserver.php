<?php

namespace App\Observers;

use App\Enums\AttendanceStatus;
use App\Models\StudentSessionReport;

/**
 * Student Session Report Observer
 *
 * Listens to StudentSessionReport changes and triggers secondary updates:
 * - Updates pivot table counters for group circles
 *
 * NOTE: QuranProgress model has been removed. Progress is now calculated
 * dynamically from session reports using the Report Services.
 *
 * CRITICAL: This observer ONLY reads attendance data, never modifies it.
 * Auto-attendance system (LiveKit webhooks) has exclusive write access to:
 * - attendance_status
 * - actual_attendance_minutes
 * - is_late
 * - meeting_enter_time
 * - meeting_leave_time
 */
class StudentSessionReportObserver
{
    /**
     * Handle the StudentSessionReport "updated" event.
     *
     * Triggered AFTER auto-attendance or teacher evaluation updates the report.
     * Updates pivot table counters for group circles.
     */
    public function updated(StudentSessionReport $report): void
    {
        // Only proceed if attendance status changed (for pivot counter updates)
        if (! $report->isDirty('attendance_status')) {
            return;
        }

        // Update pivot table counters (group circles only)
        if ($report->session->circle_id) {
            $this->updateGroupCirclePivotCounters($report);
        }
    }

    /**
     * Update pivot table counters for group circles
     *
     * Updates attendance_count and missed_sessions in quran_circle_students pivot table
     * Uses Eloquent to maintain tenant scoping
     */
    protected function updateGroupCirclePivotCounters(StudentSessionReport $report): void
    {
        $session = $report->session;
        $circle = $session->circle;

        if (! $circle) {
            return;
        }

        // Calculate current attendance counts for this student in this circle using Eloquent
        $attendedStatuses = [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value];

        $attended = StudentSessionReport::whereHas('session', function ($query) use ($circle) {
            $query->where('circle_id', $circle->id);
        })
            ->where('student_id', $report->student_id)
            ->whereIn('attendance_status', $attendedStatuses)
            ->count();

        $missed = StudentSessionReport::whereHas('session', function ($query) use ($circle) {
            $query->where('circle_id', $circle->id);
        })
            ->where('student_id', $report->student_id)
            ->where('attendance_status', AttendanceStatus::ABSENT->value)
            ->count();

        // Update pivot table using Eloquent relationship
        $circle->students()->updateExistingPivot($report->student_id, [
            'attendance_count' => $attended,
            'missed_sessions' => $missed,
        ]);
    }

    /**
     * Handle the StudentSessionReport "deleted" event.
     *
     * When a report is deleted, we should recalculate circle progress
     */
    public function deleted(StudentSessionReport $report): void
    {
        // Recalculate circle progress after deletion
        $this->updateCircleProgress($report);

        // Recalculate pivot counters for group circles
        if ($report->session?->circle_id) {
            $this->updateGroupCirclePivotCounters($report);
        }
    }

    /**
     * Handle the StudentSessionReport "restored" event.
     *
     * When a report is restored, recalculate progress
     */
    public function restored(StudentSessionReport $report): void
    {
        // Treat restoration like an update
        $this->updated($report);
    }

    /**
     * Update circle progress after session report changes
     */
    protected function updateCircleProgress(StudentSessionReport $report): void
    {
        // Progress tracking is now handled by QuranCircleReportService
    }
}
