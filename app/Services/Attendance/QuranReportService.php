<?php

namespace App\Services\Attendance;

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
     * Get the attendance threshold percentage for Quran sessions.
     * Uses academy settings, falls back to config default.
     */
    protected function getAttendanceThreshold($session): float
    {
        return $session->academy?->settings?->default_attendance_threshold_percentage
            ?? config('business.attendance.threshold_percent', 80);
    }

    /**
     * Get the grace period for Quran sessions.
     * Uses academy settings for late tolerance.
     */
    protected function getGracePeriod($session): int
    {
        return $session->academy?->settings?->default_late_tolerance_minutes ?? 15;
    }

    /**
     * Get the performance field name for Quran sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'new_memorization_degree'; // Quran primary performance metric
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

        if (! $teacher) {
            return 0;
        }

        foreach ($students as $student) {
            if (! $student) {
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
