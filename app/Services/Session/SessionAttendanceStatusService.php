<?php

namespace App\Services\Session;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Services\AcademicAttendanceService;
use App\Services\UnifiedAttendanceService;
use Illuminate\Support\Carbon;

/**
 * Service for handling session attendance status queries
 */
class SessionAttendanceStatusService
{
    private const DEFAULT_DURATION_MINUTES = 60;

    public function __construct(
        private AcademicAttendanceService $academicAttendanceService,
        private UnifiedAttendanceService $unifiedAttendanceService
    ) {}

    /**
     * Get attendance status for a session and user
     */
    public function getAttendanceStatus($session, $user): array
    {
        $statusValue = $session->status instanceof SessionStatus
            ? $session->status->value
            : $session->status;

        // Check if user has ever joined this session
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        $hasEverJoined = $meetingAttendance !== null;

        // Handle session timing and states
        $now = now();
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $sessionStart
            ? $sessionStart->copy()->addMinutes($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES)
            : null;

        $isBeforeSession = $sessionStart && $now->isBefore($sessionStart);
        $isDuringSession = $sessionStart && $sessionEnd && $now->between($sessionStart, $sessionEnd);
        $isAfterSession = $sessionEnd && $now->isAfter($sessionEnd);

        // Completed or ended sessions
        if ($statusValue === 'completed' || $isAfterSession) {
            return $this->buildCompletedAttendanceResponse($session, $user, $meetingAttendance, $hasEverJoined);
        }

        // Before session
        if ($isBeforeSession) {
            return [
                'is_currently_in_meeting' => false,
                'attendance_status' => 'not_started',
                'attendance_percentage' => '0.00',
                'duration_minutes' => 0,
                'join_count' => 0,
                'session_state' => 'scheduled',
                'has_ever_joined' => false,
                'minutes_until_start' => max(0, ceil($now->diffInMinutes($sessionStart, false))),
            ];
        }

        // Active session - use real-time data
        return $this->buildActiveAttendanceResponse($session, $user, $hasEverJoined, $isDuringSession, $statusValue);
    }

    /**
     * Build completed attendance response
     */
    private function buildCompletedAttendanceResponse($session, $user, $meetingAttendance, bool $hasEverJoined): array
    {
        $sessionReport = $this->getSessionReport($session, $user);

        if ($sessionReport) {
            $attendanceStatus = $sessionReport->attendance_status ?? 'absent';
            $duration = $sessionReport->actual_attendance_minutes ?? 0;

            if (!$hasEverJoined) {
                $attendanceStatus = 'not_attended';
            } elseif ($duration > 0 && in_array($attendanceStatus, ['left', 'partial'])) {
                $attendanceStatus = 'partial_attendance';
            }

            return [
                'is_currently_in_meeting' => false,
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => number_format($sessionReport->attendance_percentage ?? 0, 2),
                'duration_minutes' => $duration,
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'is_late' => $sessionReport->is_late ?? false,
                'late_minutes' => $sessionReport->late_minutes ?? 0,
                'last_updated' => $sessionReport->updated_at,
                'session_state' => 'completed',
                'has_ever_joined' => $hasEverJoined,
            ];
        }

        return [
            'is_currently_in_meeting' => false,
            'attendance_status' => $hasEverJoined ? 'not_enough_time' : 'not_attended',
            'attendance_percentage' => '0.00',
            'duration_minutes' => $meetingAttendance?->total_duration_minutes ?? 0,
            'join_count' => $meetingAttendance?->join_count ?? 0,
            'session_state' => 'completed',
            'has_ever_joined' => $hasEverJoined,
        ];
    }

    /**
     * Build active attendance response
     */
    private function buildActiveAttendanceResponse($session, $user, bool $hasEverJoined, bool $isDuringSession, string $statusValue): array
    {
        if ($session instanceof AcademicSession) {
            $status = $this->academicAttendanceService->getCurrentAttendanceStatus($session, $user);
        } else {
            $status = $this->unifiedAttendanceService->getCurrentAttendanceStatus($session, $user);
        }

        $status['session_state'] = $isDuringSession ? 'ongoing' : 'scheduled';
        $status['has_ever_joined'] = $hasEverJoined;

        if (!$hasEverJoined && ($statusValue === 'scheduled' || $isDuringSession)) {
            $status['attendance_status'] = 'not_joined_yet';
        }

        return $status;
    }

    /**
     * Get session report based on session type
     */
    private function getSessionReport($session, $user)
    {
        if ($session instanceof AcademicSession) {
            return AcademicSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();
        }

        if ($session instanceof InteractiveCourseSession) {
            return InteractiveSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();
        }

        return StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();
    }
}
