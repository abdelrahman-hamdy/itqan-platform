<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Enums\SessionStatus;

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
        return 'session_id'; // StudentSessionReport uses 'session_id'
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
     * Quran sessions: Configurable grace period from circle settings, 70% attendance threshold
     */
    protected function determineAttendanceStatus(
        MeetingAttendance $meetingAttendance,
        $session,
        int $actualMinutes,
        float $attendancePercentage
    ): string {
        // Quran sessions typically require 70% attendance to be considered "present"
        $requiredPercentage = 70;

        // Get grace period from circle settings (individual or group)
        $graceTimeMinutes = $this->getGracePeriodForSession($session);

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
            return $isLate ? AttendanceStatus::LATE->value : AttendanceStatus::ATTENDED->value;
        } elseif ($attendancePercentage > 0) {
            return AttendanceStatus::LEAVED->value; // Left early / partial attendance
        } else {
            return AttendanceStatus::ABSENT->value;
        }
    }

    /**
     * Get the performance field name for Quran sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'new_memorization_degree'; // Quran primary performance metric
    }

    /**
     * Get grace period from academy settings
     */
    protected function getGracePeriodForSession($session): int
    {
        return $session->academy?->settings?->default_late_tolerance_minutes ?? 15;
    }

    // ========================================
    // Quran-Specific Methods
    // ========================================

    /**
     * Record Quran performance evaluation (memorization and reservation degrees)
     */
    public function recordQuranEvaluation(
        StudentSessionReport $report,
        ?float $newMemorizationDegree = null,
        ?float $reservationDegree = null,
        ?string $notes = null
    ): StudentSessionReport {
        $updateData = ['evaluated_at' => now()];

        if ($newMemorizationDegree !== null) {
            if ($newMemorizationDegree < 0 || $newMemorizationDegree > 10) {
                throw new \InvalidArgumentException('New memorization degree must be between 0 and 10');
            }
            $updateData['new_memorization_degree'] = $newMemorizationDegree;
        }

        if ($reservationDegree !== null) {
            if ($reservationDegree < 0 || $reservationDegree > 10) {
                throw new \InvalidArgumentException('Reservation degree must be between 0 and 10');
            }
            $updateData['reservation_degree'] = $reservationDegree;
        }

        if ($notes !== null) {
            $updateData['notes'] = $notes;
        }

        $report->update($updateData);

        return $report->fresh();
    }

    /**
     * Get students for a Quran session
     */
    protected function getSessionStudents(QuranSession $session): \Illuminate\Support\Collection
    {
        // For individual sessions
        if ($session->session_type === 'individual' && $session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        // For group sessions (circle)
        if ($session->circle) {
            return $session->circle->students ?? collect();
        }

        // For individual circle sessions
        if ($session->individualCircle) {
            $studentProfile = $session->individualCircle->studentProfile;
            if ($studentProfile && $studentProfile->user) {
                return collect([$studentProfile->user]);
            }
        }

        return collect();
    }

    /**
     * Create reports for all students in a Quran session
     */
    public function createReportsForSession(QuranSession $session): int
    {
        $students = $this->getSessionStudents($session);
        $teacher = $this->getSessionTeacher($session);
        $createdCount = 0;

        if (!$teacher) {
            return 0;
        }

        foreach ($students as $student) {
            if (!$student) {
                continue;
            }

            StudentSessionReport::firstOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'academy_id' => $session->academy_id,
                    'is_calculated' => false,
                ]
            );

            $createdCount++;
        }

        return $createdCount;
    }
}
