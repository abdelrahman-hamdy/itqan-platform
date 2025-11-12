<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\MeetingAttendance;
use App\Models\User;

/**
 * Academic Report Service
 *
 * Handles attendance synchronization for academic sessions.
 * Extends BaseReportSyncService with Academic-specific logic.
 */
class AcademicReportService extends BaseReportSyncService
{
    /**
     * Get the report model class for Academic sessions
     */
    protected function getReportClass(): string
    {
        return AcademicSessionReport::class;
    }

    /**
     * Get the foreign key for Academic session reports
     */
    protected function getSessionReportForeignKey(): string
    {
        return 'session_id'; // AcademicSessionReport uses 'session_id'
    }

    /**
     * Get the teacher for an Academic session
     */
    protected function getSessionTeacher($session): ?User
    {
        return $session->academicTeacher;
    }

    /**
     * Determine attendance status for Academic sessions
     * Academic sessions: Fixed 15 min grace, 80% attendance threshold
     * (From AcademicAttendanceService:380-408)
     */
    protected function determineAttendanceStatus(
        MeetingAttendance $meetingAttendance,
        $session,
        int $actualMinutes,
        float $attendancePercentage
    ): string {
        // Academic sessions typically require 80% attendance to be considered "present"
        $requiredPercentage = 80;

        // Fixed 15 minutes grace period for academic sessions
        $graceTimeMinutes = 15;

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
     * Get the performance field name for Academic sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'student_performance_grade'; // Academic performance metric (1-10)
    }

    // ========================================
    // Academic-Specific Methods
    // ========================================

    /**
     * Record academic performance grade
     */
    public function recordPerformanceGrade(
        AcademicSessionReport $report,
        int $grade,
        ?string $notes = null
    ): AcademicSessionReport {
        if ($grade < 1 || $grade > 10) {
            throw new \InvalidArgumentException('Performance grade must be between 1 and 10');
        }

        $report->update([
            'student_performance_grade' => $grade,
            'notes' => $notes,
            'evaluated_at' => now(),
        ]);

        return $report->fresh();
    }

    /**
     * Record homework for academic session
     */
    public function recordHomework(
        AcademicSessionReport $report,
        string $homeworkText,
        ?string $feedback = null
    ): AcademicSessionReport {
        $report->update([
            'homework_text' => $homeworkText,
            'homework_feedback' => $feedback,
        ]);

        return $report->fresh();
    }

    /**
     * Get students for an Academic session
     */
    protected function getSessionStudents(AcademicSession $session): \Illuminate\Support\Collection
    {
        if ($session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        if ($session->academicSubscription) {
            return $session->academicSubscription->students ?? collect();
        }

        return collect();
    }
}
