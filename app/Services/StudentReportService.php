<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentReportService
{
    /**
     * Generate or update student session report based on meeting attendance.
     *
     * Only calculates attendance for completed/absent sessions.
     * During active sessions, creates a placeholder report (is_calculated=false).
     */
    public function generateStudentReport(QuranSession $session, User $student): StudentSessionReport
    {
        return DB::transaction(function () use ($session, $student) {
            // Determine if session is in a final state
            $sessionStatus = $session->status instanceof SessionStatus
                ? $session->status
                : SessionStatus::from($session->status);
            $isSessionComplete = $sessionStatus->isFinal();

            if (! $isSessionComplete) {
                // Active session - create placeholder report without calculating attendance
                return StudentSessionReport::firstOrCreate([
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                ], [
                    'teacher_id' => $session->quran_teacher_id,
                    'academy_id' => $session->academy_id,
                    'is_calculated' => false,
                ]);
            }

            // Session is complete - calculate attendance from meeting data
            $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            $reportData = $this->calculateAttendanceData($session, $student, $meetingAttendance);

            return StudentSessionReport::updateOrCreate([
                'session_id' => $session->id,
                'student_id' => $student->id,
            ], array_merge($reportData, [
                'teacher_id' => $session->quran_teacher_id,
                'academy_id' => $session->academy_id,
                'is_calculated' => true,
                'evaluated_at' => now(),
            ]));
        });
    }

    /**
     * Calculate attendance data from meeting attendance
     */
    protected function calculateAttendanceData(QuranSession $session, User $student, ?MeetingAttendance $meetingAttendance): array
    {
        if (! $meetingAttendance) {
            return [
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'meeting_enter_time' => null,
                'meeting_leave_time' => null,
                'actual_attendance_minutes' => 0,
                'is_late' => false,
                'late_minutes' => 0,
                'attendance_percentage' => 0,
                'meeting_events' => [],
            ];
        }

        $sessionStart = $session->scheduled_at;
        $sessionDuration = $session->duration_minutes;
        $maxLateMinutes = $this->getMaxLateMinutes($session);

        // Calculate lateness
        $enterTime = $meetingAttendance->first_join_time;
        $leaveTime = $meetingAttendance->last_leave_time;
        $actualMinutes = $meetingAttendance->total_duration_minutes ?? 0;

        $isLate = false;
        $lateMinutes = 0;

        if ($enterTime && $sessionStart) {
            $minutesAfterStart = $sessionStart->diffInMinutes($enterTime, false);
            if ($minutesAfterStart > 0) {
                $isLate = $minutesAfterStart > $maxLateMinutes;
                $lateMinutes = $minutesAfterStart;
            }
        }

        // Calculate attendance status
        $attendanceStatus = $this->calculateAttendanceStatus(
            $actualMinutes,
            $sessionDuration,
            $isLate,
            $lateMinutes,
            $maxLateMinutes
        );

        // Calculate attendance percentage
        $attendancePercentage = $sessionDuration > 0 ?
            min(100, ($actualMinutes / $sessionDuration) * 100) : 0;

        return [
            'attendance_status' => $attendanceStatus,
            'meeting_enter_time' => $enterTime,
            'meeting_leave_time' => $leaveTime,
            'actual_attendance_minutes' => $actualMinutes,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'attendance_percentage' => round($attendancePercentage, 2),
            'meeting_events' => $meetingAttendance->join_leave_cycles ?? [],
        ];
    }

    /**
     * Calculate attendance status based on various factors
     */
    protected function calculateAttendanceStatus(
        int $actualMinutes,
        int $sessionDuration,
        bool $isLate,
        int $lateMinutes,
        int $maxLateMinutes
    ): string {
        if ($actualMinutes === 0) {
            return AttendanceStatus::ABSENT->value;
        }

        // Guard against division by zero - treat as absent if session has no duration
        if ($sessionDuration <= 0) {
            return AttendanceStatus::ABSENT->value;
        }

        $attendancePercentage = ($actualMinutes / $sessionDuration) * 100;

        // If too late, mark as absent
        if ($lateMinutes > $maxLateMinutes) {
            return AttendanceStatus::ABSENT->value;
        }

        // If attended less than 30%, mark as absent
        if ($attendancePercentage < 30) {
            return AttendanceStatus::ABSENT->value;
        }

        // If attended 30-50%, mark as left early (left)
        if ($attendancePercentage < 50) {
            return AttendanceStatus::LEFT->value;
        }

        // If late but attended well, mark as late
        if ($isLate && $attendancePercentage >= 50) {
            return AttendanceStatus::LATE->value;
        }

        // If attended 50%+ and not late, mark as attended
        return AttendanceStatus::ATTENDED->value;
    }

    /**
     * Get maximum allowed late minutes for session
     */
    protected function getMaxLateMinutes(QuranSession $session): int
    {
        // Get from circle settings if available, otherwise default
        if ($session->session_type === 'group' && $session->circle) {
            return $session->circle->max_late_minutes ?? 10;
        }

        if ($session->session_type === 'individual' && $session->individualCircle) {
            return $session->individualCircle->max_late_minutes ?? 10;
        }

        return 10; // Default 10 minutes
    }

    /**
     * Update student report with teacher evaluation
     */
    public function updateTeacherEvaluation(
        StudentSessionReport $report,
        int $newMemorizationDegree,
        int $reservationDegree,
        ?string $notes = null
    ): StudentSessionReport {
        $report->update([
            'new_memorization_degree' => $newMemorizationDegree,
            'reservation_degree' => $reservationDegree,
            'notes' => $notes,
            'manually_evaluated' => true,
            'evaluated_at' => now(),
        ]);

        return $report;
    }

    /**
     * Generate reports for all students in a session
     */
    public function generateSessionReports(QuranSession $session): Collection
    {
        $students = $this->getSessionStudents($session);
        $reports = collect();

        foreach ($students as $student) {
            $report = $this->generateStudentReport($session, $student);
            $reports->push($report);
        }

        return $reports;
    }

    /**
     * Get students for a session
     */
    protected function getSessionStudents(QuranSession $session): Collection
    {
        if ($session->session_type === 'group' && $session->circle) {
            // Eager load user relationship to prevent N+1 queries
            return $session->circle->students()->with('user')->get()->pluck('user')->filter();
        } elseif ($session->session_type === 'individual' && $session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        return collect();
    }

    /**
     * Get comprehensive session statistics using new report system
     */
    public function getSessionStats(QuranSession $session): array
    {
        $reports = $session->studentReports()->with('student')->get();

        return [
            'total_students' => $reports->count(),
            'attended_count' => $reports->where('attendance_status', AttendanceStatus::ATTENDED->value)->count(),
            'late_count' => $reports->where('attendance_status', AttendanceStatus::LATE->value)->count(),
            'absent_count' => $reports->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'left_count' => $reports->where('attendance_status', AttendanceStatus::LEFT->value)->count(),
            'auto_calculated_count' => $reports->where('is_calculated', true)->count(),
            'manually_evaluated_count' => $reports->where('manually_evaluated', true)->count(),
            'avg_attendance_percentage' => $reports->avg('attendance_percentage') ?: 0,
            'avg_memorization_degree' => $reports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0,
            'avg_reservation_degree' => $reports->whereNotNull('reservation_degree')->avg('reservation_degree') ?: 0,
            'reports' => $reports,
        ];
    }

    /**
     * Get student statistics across multiple sessions
     */
    public function getStudentStats(User $student, Collection $sessionIds): array
    {
        $reports = StudentSessionReport::where('student_id', $student->id)
            ->whereIn('session_id', $sessionIds)
            ->get();

        $attendedReports = $reports->whereIn('attendance_status', [
            AttendanceStatus::ATTENDED->value,
            AttendanceStatus::LATE->value,
            AttendanceStatus::LEFT->value,
        ]);

        return [
            'total_sessions' => $reports->count(),
            'attended_sessions' => $attendedReports->count(),
            'missed_sessions' => $reports->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'attendance_rate' => $reports->count() > 0 ?
                ($attendedReports->count() / $reports->count()) * 100 : 0,
            'avg_memorization_degree' => $reports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0,
            'avg_reservation_degree' => $reports->whereNotNull('reservation_degree')->avg('reservation_degree') ?: 0,
            'avg_attendance_percentage' => $reports->avg('attendance_percentage') ?: 0,
            'latest_report' => $reports->sortByDesc('created_at')->first(),
            'improvement_trend' => $this->calculateImprovementTrend($reports),
        ];
    }

    /**
     * Calculate improvement trend based on recent performance
     */
    protected function calculateImprovementTrend(Collection $reports): string
    {
        if ($reports->count() < 2) {
            return 'insufficient_data';
        }

        $recentReports = $reports->sortByDesc('created_at')->take(5);
        $olderReports = $reports->sortByDesc('created_at')->skip(5)->take(5);

        $recentAvg = $recentReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0;
        $olderAvg = $olderReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0;

        if ($recentAvg > $olderAvg + 0.5) {
            return 'improving';
        } elseif ($recentAvg < $olderAvg - 0.5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
}
