<?php

namespace App\Services;

use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QuranAttendanceService
{
    protected StudentReportService $studentReportService;

    public function __construct(StudentReportService $studentReportService)
    {
        $this->studentReportService = $studentReportService;
    }

    /**
     * Track meeting event for automatic attendance (now uses MeetingAttendance)
     */
    public function trackMeetingEvent(string $sessionId, string $studentId, string $eventType, array $eventData = []): void
    {
        try {
            $attendance = MeetingAttendance::firstOrCreate([
                'session_id' => $sessionId,
                'user_id' => $studentId,
                'user_type' => 'student',
                'session_type' => QuranSession::find($sessionId)?->session_type ?? 'unknown',
            ]);

            // Update meeting attendance based on event type
            $this->updateMeetingAttendance($attendance, $eventType, $eventData);

            // Generate/update student report after tracking event
            $session = QuranSession::find($sessionId);
            $student = User::find($studentId);

            if ($session && $student) {
                $this->studentReportService->generateStudentReport($session, $student);
            }

            Log::info('Meeting event tracked', [
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'event_type' => $eventType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track meeting event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update meeting attendance based on event type
     */
    protected function updateMeetingAttendance(MeetingAttendance $attendance, string $eventType, array $eventData): void
    {
        $now = now();

        switch ($eventType) {
            case 'join':
            case 'participant_joined':
                if (! $attendance->first_join_time) {
                    $attendance->first_join_time = $now;
                }
                $attendance->join_count = ($attendance->join_count ?? 0) + 1;

                // Add to join/leave cycles
                $cycles = $attendance->join_leave_cycles ?? [];
                $cycles[] = ['joined_at' => $now->toISOString()];
                $attendance->join_leave_cycles = $cycles;
                break;

            case 'leave':
            case 'participant_left':
                $attendance->last_leave_time = $now;
                $attendance->leave_count = ($attendance->leave_count ?? 0) + 1;

                // Update last cycle with leave time
                $cycles = $attendance->join_leave_cycles ?? [];
                if (! empty($cycles)) {
                    $lastCycleIndex = count($cycles) - 1;
                    $cycles[$lastCycleIndex]['left_at'] = $now->toISOString();

                    // Calculate duration for this cycle
                    if (isset($cycles[$lastCycleIndex]['joined_at'])) {
                        $joinTime = \Carbon\Carbon::parse($cycles[$lastCycleIndex]['joined_at']);
                        $duration = $joinTime->diffInMinutes($now);
                        $cycles[$lastCycleIndex]['duration_minutes'] = $duration;
                    }
                }
                $attendance->join_leave_cycles = $cycles;

                // Recalculate total duration
                $this->recalculateTotalDuration($attendance);
                break;
        }

        $attendance->attendance_calculated_at = $now;
        $attendance->is_calculated = true;
        $attendance->save();
    }

    /**
     * Recalculate total duration from cycles
     */
    protected function recalculateTotalDuration(MeetingAttendance $attendance): void
    {
        $cycles = $attendance->join_leave_cycles ?? [];
        $totalMinutes = 0;

        foreach ($cycles as $cycle) {
            if (isset($cycle['duration_minutes'])) {
                $totalMinutes += $cycle['duration_minutes'];
            }
        }

        $attendance->total_duration_minutes = $totalMinutes;
    }

    /**
     * Manually update student evaluation
     */
    public function updateStudentEvaluation(
        StudentSessionReport $report,
        int $newMemorizationDegree,
        int $reservationDegree,
        ?string $notes = null
    ): StudentSessionReport {
        return $this->studentReportService->updateTeacherEvaluation(
            $report,
            $newMemorizationDegree,
            $reservationDegree,
            $notes
        );
    }

    /**
     * Get comprehensive attendance statistics for a session
     */
    public function getSessionAttendanceStats(QuranSession $session): array
    {
        return $this->studentReportService->getSessionStats($session);
    }

    /**
     * Initialize attendance records for session students
     */
    public function initializeSessionAttendance(QuranSession $session): void
    {
        $students = $this->getSessionStudents($session);

        foreach ($students as $student) {
            $this->studentReportService->generateStudentReport($session, $student);
        }
    }

    /**
     * Generate all reports for session
     */
    public function generateSessionReports(QuranSession $session): Collection
    {
        return $this->studentReportService->generateSessionReports($session);
    }

    /**
     * Get students for a session
     */
    protected function getSessionStudents(QuranSession $session): Collection
    {
        if ($session->session_type === 'group' && $session->circle) {
            return $session->circle->students;
        } elseif ($session->session_type === 'individual' && $session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        return collect();
    }
}
