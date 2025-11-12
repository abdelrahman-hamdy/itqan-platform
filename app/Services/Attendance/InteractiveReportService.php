<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\MeetingAttendance;
use App\Models\User;

/**
 * Interactive Report Service
 *
 * Handles attendance synchronization for interactive course sessions.
 * Extends BaseReportSyncService with Interactive-specific logic.
 */
class InteractiveReportService extends BaseReportSyncService
{
    /**
     * Get the report model class for Interactive sessions
     */
    protected function getReportClass(): string
    {
        return InteractiveSessionReport::class;
    }

    /**
     * Get the foreign key for Interactive session reports
     */
    protected function getSessionReportForeignKey(): string
    {
        return 'session_id';
    }

    /**
     * Get the teacher for an Interactive session
     */
    protected function getSessionTeacher($session): ?User
    {
        return $session->course?->teacher ?? null;
    }

    /**
     * Determine attendance status for Interactive sessions
     * Interactive sessions: 10 min grace period, 80% attendance threshold
     */
    protected function determineAttendanceStatus(
        MeetingAttendance $meetingAttendance,
        $session,
        int $actualMinutes,
        float $attendancePercentage
    ): string {
        // Interactive sessions require 80% attendance to be considered "present"
        $requiredPercentage = 80;

        // 10 minutes grace period for interactive sessions (shorter than academic/quran)
        $graceTimeMinutes = 10;

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
     * Get the performance field name for Interactive sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'engagement_score'; // Interactive performance metric (0-10)
    }

    // ========================================
    // Interactive-Specific Methods
    // ========================================

    /**
     * Record quiz score for interactive session
     */
    public function recordQuizScore(
        InteractiveSessionReport $report,
        float $score
    ): InteractiveSessionReport {
        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException('Quiz score must be between 0 and 100');
        }

        $report->update(['quiz_score' => $score]);

        return $report->fresh();
    }

    /**
     * Record video completion percentage
     */
    public function recordVideoCompletion(
        InteractiveSessionReport $report,
        float $percentage
    ): InteractiveSessionReport {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Video completion must be between 0 and 100');
        }

        $report->update(['video_completion_percentage' => $percentage]);

        return $report->fresh();
    }

    /**
     * Record exercises completed
     */
    public function recordExercisesCompleted(
        InteractiveSessionReport $report,
        int $count
    ): InteractiveSessionReport {
        if ($count < 0) {
            throw new \InvalidArgumentException('Exercises completed cannot be negative');
        }

        $report->update(['exercises_completed' => $count]);

        return $report->fresh();
    }

    /**
     * Record engagement score
     */
    public function recordEngagementScore(
        InteractiveSessionReport $report,
        float $score
    ): InteractiveSessionReport {
        if ($score < 0 || $score > 10) {
            throw new \InvalidArgumentException('Engagement score must be between 0 and 10');
        }

        $report->update([
            'engagement_score' => $score,
            'evaluated_at' => now(),
        ]);

        return $report->fresh();
    }

    /**
     * Get students for an Interactive session
     */
    protected function getSessionStudents(InteractiveCourseSession $session): \Illuminate\Support\Collection
    {
        if ($session->course) {
            return $session->course->enrollments->pluck('student')->filter();
        }

        return collect();
    }
}
