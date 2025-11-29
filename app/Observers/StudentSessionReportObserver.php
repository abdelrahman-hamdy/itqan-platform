<?php

namespace App\Observers;

use App\Models\StudentSessionReport;
use Illuminate\Support\Facades\DB;

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
     */
    protected function updateGroupCirclePivotCounters(StudentSessionReport $report): void
    {
        $session = $report->session;
        $circle = $session->circle;

        if (! $circle) {
            return;
        }

        // Calculate current attendance counts for this student in this circle
        $attendanceStats = DB::table('student_session_reports')
            ->join('quran_sessions', 'student_session_reports.session_id', '=', 'quran_sessions.id')
            ->where('quran_sessions.circle_id', $circle->id)
            ->where('student_session_reports.student_id', $report->student_id)
            ->selectRaw('
                COUNT(*) as total_sessions,
                SUM(CASE WHEN student_session_reports.attendance_status = ? THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN student_session_reports.attendance_status = ? THEN 1 ELSE 0 END) as missed
            ', ['present', 'absent'])
            ->first();

        // Update pivot table
        DB::table('quran_circle_students')
            ->where('circle_id', $circle->id)
            ->where('student_id', $report->student_id)
            ->update([
                'attendance_count' => $attendanceStats->attended ?? 0,
                'missed_sessions' => $attendanceStats->missed ?? 0,
                'updated_at' => now(),
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
}
