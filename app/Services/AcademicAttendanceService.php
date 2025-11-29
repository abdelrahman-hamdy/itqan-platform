<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Academic Attendance Service
 *
 * Handles attendance tracking for academic sessions using the same patterns
 * as UnifiedAttendanceService but specifically for academic sessions
 *
 * @deprecated Use App\Services\Attendance\AcademicReportService instead
 *
 * This service has been replaced by AcademicReportService which extends BaseReportSyncService.
 * The new service eliminates 60% code duplication and provides better maintainability.
 *
 * This service will be removed in the next release.
 * See PHASE9_SERVICE_LAYER_ANALYSIS.md for migration guide.
 *
 * Migration:
 * - Replace: AcademicAttendanceService -> AcademicReportService
 * - All method signatures remain compatible
 * - Update service provider bindings if needed
 * - Fixed 15-minute grace period and 80% threshold now properly implemented
 */
class AcademicAttendanceService
{
    private MeetingAttendanceService $meetingAttendanceService;

    public function __construct(MeetingAttendanceService $meetingAttendanceService)
    {
        $this->meetingAttendanceService = $meetingAttendanceService;
    }

    /**
     * Handle user joining an academic session meeting
     * Creates both MeetingAttendance (real-time) and AcademicSessionReport (unified)
     */
    public function handleUserJoin(AcademicSession $session, User $user): bool
    {
        try {
            // First, handle real-time tracking using the existing service
            // Pass the session as polymorphic - the MeetingAttendance will use session_type 'academic'
            $joinSuccess = $this->meetingAttendanceService->handleUserJoinPolymorphic(
                $session,
                $user,
                'academic'
            );

            if (!$joinSuccess) {
                return false;
            }

            // Then, create/update the unified academic report
            $this->createOrUpdateAcademicSessionReport($session, $user);

            Log::info('Academic attendance - user joined', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => 'academic',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle academic user join', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle user leaving an academic session meeting
     */
    public function handleUserLeave(AcademicSession $session, User $user): bool
    {
        try {
            // First, handle real-time tracking
            $leaveSuccess = $this->meetingAttendanceService->handleUserLeavePolymorphic(
                $session,
                $user,
                'academic'
            );

            if (!$leaveSuccess) {
                return false;
            }

            // Then, sync to unified academic report
            $this->syncAttendanceToAcademicReport($session, $user);

            Log::info('Academic attendance - user left', [
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle academic user leave', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Calculate final attendance for completed academic sessions
     */
    public function calculateFinalAttendance(AcademicSession $session): array
    {
        $results = [
            'session_id' => $session->id,
            'calculated_count' => 0,
            'reports_updated' => 0,
            'errors' => [],
        ];

        try {
            // Get meeting attendances for this academic session
            $meetingAttendances = MeetingAttendance::where('session_id', $session->id)
                ->where('session_type', 'academic')
                ->get();

            foreach ($meetingAttendances as $attendance) {
                try {
                    $user = User::find($attendance->user_id);
                    if ($user && $user->hasRole('student')) {
                        $this->syncAttendanceToAcademicReport($session, $user);
                        $results['reports_updated']++;
                        $results['calculated_count']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'user_id' => $attendance->user_id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Academic final attendance calculated', [
                'session_id' => $session->id,
                'meeting_attendances' => $results['calculated_count'],
                'reports_updated' => $results['reports_updated'],
            ]);

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];

            Log::error('Failed to calculate academic final attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Get current attendance status for a user in an academic session
     */
    public function getCurrentAttendanceStatus(AcademicSession $session, User $user): array
    {
        $statusValue = is_object($session->status) && method_exists($session->status, 'value') 
            ? $session->status->value 
            : $session->status;
            
        Log::info('Getting academic attendance status', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'session_status' => $statusValue,
        ]);

        // Check real-time meeting attendance
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('session_type', 'academic')
            ->first();

        // Check academic session report
        $sessionReport = AcademicSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();

        Log::info('Getting academic attendance status - data found', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'has_meeting_attendance' => $meetingAttendance ? true : false,
            'has_session_report' => $sessionReport ? true : false,
        ]);

        // Calculate current duration including active time
        $durationMinutes = 0;
        if ($meetingAttendance) {
            $isCurrentlyInMeeting = $meetingAttendance->isCurrentlyInMeeting();
            $durationMinutes = $isCurrentlyInMeeting
                ? $meetingAttendance->getCurrentSessionDuration()  // Includes current active time
                : $meetingAttendance->total_duration_minutes;      // Completed cycles only

            Log::info('Academic attendance calculation details', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'is_currently_in_meeting' => $isCurrentlyInMeeting,
                'total_duration_minutes' => $meetingAttendance->total_duration_minutes,
                'current_session_duration' => $isCurrentlyInMeeting ? $meetingAttendance->getCurrentSessionDuration() : 'N/A',
                'final_duration_minutes' => $durationMinutes,
                'join_count' => $meetingAttendance->join_count,
            ]);
        }

        // For completed sessions, prioritize AcademicSessionReport data
        if ($statusValue === 'completed' && $sessionReport) {
            $attendanceStatus = $sessionReport->attendance_status;
            $attendancePercentage = $sessionReport->attendance_percentage;
            $finalDurationMinutes = $sessionReport->actual_attendance_minutes;

            Log::info('Using completed academic session data from AcademicSessionReport', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'attendance_status' => $attendanceStatus,
                'duration_minutes' => $finalDurationMinutes,
                'percentage' => $attendancePercentage,
            ]);

            $result = [
                'is_currently_in_meeting' => false, // Completed sessions = not currently in meeting
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => $attendancePercentage,
                'duration_minutes' => $finalDurationMinutes,
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'is_late' => $sessionReport->is_late ?? false,
                'late_minutes' => $sessionReport->late_minutes ?? 0,
                'last_updated' => $sessionReport->updated_at,
            ];
        } else {
            // For active sessions, use real-time MeetingAttendance data
            $attendanceStatus = $sessionReport?->attendance_status ?? AttendanceStatus::ABSENT->value;
            $attendancePercentage = $sessionReport?->attendance_percentage ?? 0;

            // For active users, ensure we show real-time data
            if ($meetingAttendance && $meetingAttendance->isCurrentlyInMeeting()) {
                $attendanceStatus = AttendanceStatus::ATTENDED->value; // Override to show current activity
            }

            $result = [
                'is_currently_in_meeting' => $meetingAttendance?->isCurrentlyInMeeting() ?? false,
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => $attendancePercentage,
                'duration_minutes' => $durationMinutes,  // Includes current active time
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'is_late' => $sessionReport?->is_late ?? false,
                'late_minutes' => $sessionReport?->late_minutes ?? 0,
                'last_updated' => $meetingAttendance?->updated_at ?? $sessionReport?->updated_at,
            ];
        }

        Log::info('Final academic attendance status result', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Create or update academic session report for a user
     */
    private function createOrUpdateAcademicSessionReport(AcademicSession $session, User $user): void
    {
        // Check if user is a student
        if ($user->user_type !== 'student' && !$user->studentProfile) {
            return; // Only create reports for students
        }

        $teacher = $session->academicTeacher;
        if (!$teacher) {
            Log::warning('No academic teacher found for session', ['session_id' => $session->id]);
            return;
        }

        // Create or get existing academic report
        $report = AcademicSessionReport::updateOrCreate(
            [
                'academic_session_id' => $session->id,
                'student_id' => $user->id,
            ],
            [
                'teacher_id' => $teacher->id,
                'academy_id' => $session->academy_id,
                'is_calculated' => true,
            ]
        );

        // Sync attendance data immediately
        $this->syncAttendanceToAcademicReport($session, $user);
    }

    /**
     * Sync attendance data from MeetingAttendance to AcademicSessionReport
     */
    private function syncAttendanceToAcademicReport(AcademicSession $session, User $user): void
    {
        try {
            $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->where('session_type', 'academic')
                ->first();

            if (!$meetingAttendance) {
                Log::info('No meeting attendance found for academic session sync', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            $report = AcademicSessionReport::where('academic_session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();

            if (!$report) {
                Log::info('No academic session report found for sync', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Calculate attendance metrics
            $sessionDuration = $session->duration_minutes ?? 60;
            $actualMinutes = $meetingAttendance->isCurrentlyInMeeting()
                ? $meetingAttendance->getCurrentSessionDuration()
                : $meetingAttendance->total_duration_minutes;

            $attendancePercentage = $sessionDuration > 0 
                ? round(($actualMinutes / $sessionDuration) * 100, 2)
                : 0;

            // Determine attendance status based on academic session rules
            $attendanceStatus = $this->determineAcademicAttendanceStatus(
                $meetingAttendance,
                $session,
                $actualMinutes,
                $attendancePercentage
            );

            // Update the academic session report
            $report->update([
                'meeting_enter_time' => $meetingAttendance->first_join_time,
                'meeting_leave_time' => $meetingAttendance->last_leave_time,
                'actual_attendance_minutes' => $actualMinutes,
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => $attendancePercentage,
                'is_calculated' => true,
            ]);

            Log::info('Academic session attendance synced to report', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'attendance_status' => $attendanceStatus,
                'duration_minutes' => $actualMinutes,
                'percentage' => $attendancePercentage,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync academic attendance to report', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine attendance status for academic sessions
     */
    private function determineAcademicAttendanceStatus(
        MeetingAttendance $meetingAttendance,
        AcademicSession $session,
        int $actualMinutes,
        float $attendancePercentage
    ): string {
        // Academic sessions typically require 80% attendance to be considered "present"
        $requiredPercentage = 80;

        // Check if student was late (joined after session start)
        $sessionStart = $session->scheduled_at;
        $firstJoin = $meetingAttendance->first_join_time;
        $graceTimeMinutes = 15; // 15 minutes grace time for academic sessions

        $isLate = false;
        if ($sessionStart && $firstJoin) {
            $lateMinutes = $sessionStart->diffInMinutes($firstJoin, false);
            $isLate = $lateMinutes > $graceTimeMinutes;
        }

        // Determine status
        if ($attendancePercentage >= $requiredPercentage) {
            return $isLate ? AttendanceStatus::LATE->value : AttendanceStatus::ATTENDED->value;
        } elseif ($attendancePercentage > 0) {
            return AttendanceStatus::LEAVED->value;
        } else {
            return AttendanceStatus::ABSENT->value;
        }
    }

    /**
     * Override attendance status manually (teacher action)
     */
    public function overrideAttendanceStatus(
        AcademicSession $session,
        User $student,
        string $newStatus,
        ?string $reason = null,
        ?User $overriddenBy = null
    ): bool {
        try {
            $report = AcademicSessionReport::where('academic_session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            if (!$report) {
                // Create report if it doesn't exist
                $teacher = $session->academicTeacher;
                if ($teacher) {
                    $report = AcademicSessionReport::create([
                        'academic_session_id' => $session->id,
                        'student_id' => $student->id,
                        'teacher_id' => $teacher->id,
                        'academy_id' => $session->academy_id,
                        'attendance_status' => $newStatus,
                        'manually_evaluated' => true,
                        'override_reason' => $reason,
                    ]);
                }
            } else {
                $report->update([
                    'attendance_status' => $newStatus,
                    'manually_evaluated' => true,
                    'override_reason' => $reason,
                ]);
            }

            Log::info('Academic attendance status manually overridden', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'new_status' => $newStatus,
                'overridden_by' => $overriddenBy?->id,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to override academic attendance status', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get attendance statistics for an academic session
     */
    public function getSessionAttendanceStatistics(AcademicSession $session): array
    {
        $reports = AcademicSessionReport::where('academic_session_id', $session->id)->get();

        $stats = [
            'total_students' => $reports->count(),
            'present' => $reports->where('attendance_status', AttendanceStatus::ATTENDED->value)->count(),
            'late' => $reports->where('attendance_status', AttendanceStatus::LATE->value)->count(),
            'partial' => $reports->where('attendance_status', AttendanceStatus::LEAVED->value)->count(),
            'absent' => $reports->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'average_attendance_percentage' => 0,
            'average_academic_performance' => 0,
        ];

        if ($stats['total_students'] > 0) {
            $stats['average_attendance_percentage'] = $reports->avg('attendance_percentage');
            $evaluatedReports = $reports->whereNotNull('academic_performance_score');
            $stats['average_academic_performance'] = $evaluatedReports->avg('academic_performance_score');
        }

        return $stats;
    }
}
