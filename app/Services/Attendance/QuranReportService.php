<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;

/**
 * Quran Report Service
 *
 * Handles attendance synchronization for Quran sessions.
 * Extends BaseReportSyncService with Quran-specific logic.
 */
class QuranReportService extends BaseReportSyncService
{
    /**
     * Get the report model class for Quran sessions
     */
    protected function getReportClass(): string
    {
        return StudentSessionReport::class;
    }

    /**
     * Get the foreign key for Quran session reports
     */
    protected function getSessionReportForeignKey(): string
    {
        return 'session_id';
    }

    /**
     * Get the teacher for a Quran session
     */
    protected function getSessionTeacher($session): ?User
    {
        return $session->quranTeacher;
    }

    /**
     * Determine attendance status for Quran sessions
     * Uses configurable grace period from circle settings
     */
    protected function determineAttendanceStatus(
        MeetingAttendance $meetingAttendance,
        $session,
        int $actualMinutes,
        float $attendancePercentage
    ): string {
        // Quran sessions require 80% attendance to be considered "present"
        $requiredPercentage = 80;

        // Get grace period from circle settings (default 15 minutes)
        $graceTimeMinutes = 15;

        if ($session instanceof QuranSession) {
            if ($session->session_type === 'individual' && $session->individualCircle) {
                $graceTimeMinutes = $session->individualCircle->late_join_grace_period_minutes ?? 15;
            } elseif ($session->session_type === 'group' && $session->circle) {
                $graceTimeMinutes = $session->circle->late_join_grace_period_minutes ?? 15;
            }
        }

        // Check if student was late (joined after session start)
        $sessionStart = $session->scheduled_at;
        $firstJoin = $meetingAttendance->first_join_time;
        $isLate = false;

        if ($sessionStart && $firstJoin) {
            $lateMinutes = $sessionStart->diffInMinutes($firstJoin, false);
            $isLate = $lateMinutes > $graceTimeMinutes;
        }

        // Determine status based on attendance percentage
        if ($attendancePercentage >= $requiredPercentage) {
            return $isLate ? AttendanceStatus::LATE->value : AttendanceStatus::PRESENT->value;
        } elseif ($attendancePercentage > 0) {
            return AttendanceStatus::PARTIAL->value;
        } else {
            return AttendanceStatus::ABSENT->value;
        }
    }

    /**
     * Get the performance field name for Quran sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'new_memorization_degree'; // Quran performance metric
    }

    // ========================================
    // Quran-Specific Methods
    // ========================================

    /**
     * Record teacher evaluation for Quran session
     */
    public function recordTeacherEvaluation(
        StudentSessionReport $report,
        ?float $newMemorizationDegree = null,
        ?float $reservationDegree = null,
        ?string $notes = null
    ): StudentSessionReport {
        $report->update([
            'new_memorization_degree' => $newMemorizationDegree,
            'reservation_degree' => $reservationDegree,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_overridden' => true,
        ]);

        return $report->fresh();
    }

    /**
     * Get comprehensive attendance statistics for a Quran session
     */
    public function getSessionStats(QuranSession $session): array
    {
        $stats = $this->getSessionAttendanceStatistics($session);

        // Add Quran-specific metrics
        $reports = StudentSessionReport::where('session_id', $session->id)->get();

        $stats['average_new_memorization'] = $reports->whereNotNull('new_memorization_degree')
            ->avg('new_memorization_degree') ?? 0;

        $stats['average_reservation'] = $reports->whereNotNull('reservation_degree')
            ->avg('reservation_degree') ?? 0;

        return $stats;
    }

    /**
     * Generate all reports for a Quran session
     */
    public function generateSessionReports(QuranSession $session): \Illuminate\Support\Collection
    {
        $students = $this->getSessionStudents($session);
        $reports = collect();

        foreach ($students as $student) {
            $this->createOrUpdateSessionReport($session, $student);

            $report = StudentSessionReport::where('session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            if ($report) {
                $reports->push($report);
            }
        }

        return $reports;
    }

    /**
     * Get students for a Quran session
     */
    protected function getSessionStudents(QuranSession $session): \Illuminate\Support\Collection
    {
        if ($session->session_type === 'group' && $session->circle) {
            return $session->circle->students;
        } elseif ($session->session_type === 'individual' && $session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        return collect();
    }
}
