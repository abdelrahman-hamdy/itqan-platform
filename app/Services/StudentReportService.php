<?php

namespace App\Services;

use App\Contracts\StudentReportServiceInterface;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\Traits\AttendanceCalculatorTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentReportService implements StudentReportServiceInterface
{
    use AttendanceCalculatorTrait;

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
     * Calculate attendance data from meeting attendance using the
     * percentage-based thresholds from SessionSettingsService.
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

        $sessionDuration = $session->duration_minutes ?? 60;
        $enterTime = $meetingAttendance->first_join_time;
        $leaveTime = $meetingAttendance->last_leave_time;
        $actualMinutes = $meetingAttendance->total_duration_minutes ?? 0;

        [$fullPercent, $partialPercent] = app(SessionSettingsService::class)
            ->getAttendanceThresholdsForUserType($session, 'student');

        $attendanceStatus = $this->calculateAttendanceStatus(
            $enterTime,
            $sessionDuration,
            $actualMinutes,
            $fullPercent,
            $partialPercent
        );

        $attendancePercentage = $sessionDuration > 0 ?
            min(100, ($actualMinutes / $sessionDuration) * 100) : 0;

        return [
            'attendance_status' => $attendanceStatus,
            'meeting_enter_time' => $enterTime,
            'meeting_leave_time' => $leaveTime,
            'actual_attendance_minutes' => $actualMinutes,
            'is_late' => false,
            'late_minutes' => 0,
            'attendance_percentage' => round($attendancePercentage, 2),
            'meeting_events' => $meetingAttendance->join_leave_cycles ?? [],
        ];
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
            'partially_attended_count' => $reports->where('attendance_status', AttendanceStatus::PARTIALLY_ATTENDED->value)->count(),
            'absent_count' => $reports->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'late_count' => $reports->where('attendance_status', AttendanceStatus::LATE->value)->count(),
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

        $attendedReports = $reports->whereIn('attendance_status', AttendanceStatus::presentValues());

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
