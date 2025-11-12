<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Unified Attendance Service
 *
 * Combines real-time meeting tracking (MeetingAttendance) with
 * comprehensive session reports (StudentSessionReport)
 *
 * @deprecated Use App\Services\Attendance\QuranReportService or App\Services\Attendance\AcademicReportService instead
 *
 * This service has been replaced by specialized services that extend BaseReportSyncService:
 * - For Quran sessions: Use QuranReportService
 * - For Academic sessions: Use AcademicReportService
 * - For Interactive sessions: Use InteractiveReportService
 *
 * The new services eliminate 70% code duplication and provide better maintainability.
 *
 * This service will be removed in the next release.
 * See PHASE9_SERVICE_LAYER_ANALYSIS.md for migration guide.
 *
 * Migration:
 * - Replace polymorphic methods with session-specific services
 * - Update service provider bindings
 * - See line 658 for disabled buggy code that has been fixed in new services
 */
class UnifiedAttendanceService
{
    private MeetingAttendanceService $meetingAttendanceService;

    public function __construct(MeetingAttendanceService $meetingAttendanceService)
    {
        $this->meetingAttendanceService = $meetingAttendanceService;
    }

    /**
     * Handle user joining a meeting (polymorphic)
     * Creates both MeetingAttendance (real-time) and session report (unified)
     */
    public function handleUserJoinPolymorphic($session, User $user, string $sessionType): bool
    {
        try {
            // First, handle real-time tracking
            $joinSuccess = $this->meetingAttendanceService->handleUserJoin($session, $user);

            if (! $joinSuccess) {
                return false;
            }

            // Then, create/update the appropriate session report
            $this->createOrUpdateSessionReportPolymorphic($session, $user, $sessionType);

            Log::info('Unified attendance - user joined (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'session_class' => get_class($session),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle unified user join (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle user joining a meeting
     * Creates both MeetingAttendance (real-time) and StudentSessionReport (unified)
     */
    public function handleUserJoin(QuranSession $session, User $user): bool
    {
        try {
            // First, handle real-time tracking
            $joinSuccess = $this->meetingAttendanceService->handleUserJoin($session, $user);

            if (! $joinSuccess) {
                return false;
            }

            // Then, create/update the unified report
            $this->createOrUpdateSessionReport($session, $user);

            Log::info('Unified attendance - user joined', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $session->session_type,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle unified user join', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle user leaving a meeting (polymorphic)
     */
    public function handleUserLeavePolymorphic($session, User $user, string $sessionType): bool
    {
        try {
            // First, handle real-time tracking
            $leaveSuccess = $this->meetingAttendanceService->handleUserLeave($session, $user);

            if (! $leaveSuccess) {
                return false;
            }

            // Then, sync to appropriate report
            $this->syncAttendanceToReportPolymorphic($session, $user, $sessionType);

            Log::info('Unified attendance - user left (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle unified user leave (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle user leaving a meeting
     */
    public function handleUserLeave(QuranSession $session, User $user): bool
    {
        try {
            // First, handle real-time tracking
            $leaveSuccess = $this->meetingAttendanceService->handleUserLeave($session, $user);

            if (! $leaveSuccess) {
                return false;
            }

            // Then, sync to unified report
            $this->syncAttendanceToReport($session, $user);

            Log::info('Unified attendance - user left', [
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle unified user leave', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Calculate final attendance for completed sessions
     */
    public function calculateFinalAttendance(QuranSession $session): array
    {
        $results = [
            'session_id' => $session->id,
            'calculated_count' => 0,
            'reports_updated' => 0,
            'errors' => [],
        ];

        try {
            // First calculate meeting attendance
            $meetingResults = $this->meetingAttendanceService->calculateFinalAttendance($session);
            $results['calculated_count'] = $meetingResults['calculated_count'];

            // Then sync all to session reports
            $meetingAttendances = $session->meetingAttendances()->calculated()->get();

            foreach ($meetingAttendances as $attendance) {
                try {
                    $user = User::find($attendance->user_id);
                    if ($user && $user->hasRole('student')) {
                        $this->syncAttendanceToReport($session, $user);
                        $results['reports_updated']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'user_id' => $attendance->user_id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Unified final attendance calculated', [
                'session_id' => $session->id,
                'meeting_attendances' => $results['calculated_count'],
                'reports_updated' => $results['reports_updated'],
            ]);

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];

            Log::error('Failed to calculate unified final attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Get current attendance status for a user in a session
     */
    public function getCurrentAttendanceStatus(QuranSession $session, User $user): array
    {
        // CRITICAL FIX: Completely disable sync to avoid errors - use stored data only
        $statusValue = is_object($session->status) && method_exists($session->status, 'value')
            ? $session->status->value
            : $session->status;

        Log::info('Sync disabled - using stored data for all sessions', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'session_status' => $statusValue,
        ]);

        // Check real-time meeting attendance
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        // Check session report (refreshed after sync)
        $sessionReport = StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();

        Log::info('Getting attendance status', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'has_meeting_attendance' => $meetingAttendance ? true : false,
            'has_session_report' => $sessionReport ? true : false,
        ]);

        // CRITICAL FIX: Use current session duration for active users
        $durationMinutes = 0;
        if ($meetingAttendance) {
            // If user is currently in meeting, include current time
            $isCurrentlyInMeeting = $meetingAttendance->isCurrentlyInMeeting();
            $durationMinutes = $isCurrentlyInMeeting
                ? $meetingAttendance->getCurrentSessionDuration()  // Includes current active time
                : $meetingAttendance->total_duration_minutes;      // Completed cycles only

            Log::info('Attendance calculation details', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'is_currently_in_meeting' => $isCurrentlyInMeeting,
                'total_duration_minutes' => $meetingAttendance->total_duration_minutes,
                'current_session_duration' => $isCurrentlyInMeeting ? $meetingAttendance->getCurrentSessionDuration() : 'N/A',
                'final_duration_minutes' => $durationMinutes,
                'join_count' => $meetingAttendance->join_count,
                'cycles_count' => count($meetingAttendance->join_leave_cycles ?? []),
            ]);
        }

        // CRITICAL FIX: For completed sessions, prioritize StudentSessionReport data
        if ($statusValue === 'completed' && $sessionReport) {
            // Use stored report data for completed sessions (most reliable)
            $attendanceStatus = $sessionReport->attendance_status;
            $attendancePercentage = $sessionReport->attendance_percentage;
            $finalDurationMinutes = $sessionReport->actual_attendance_minutes;

            Log::info('Using completed session data from StudentSessionReport', [
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
                $attendanceStatus = AttendanceStatus::PRESENT->value; // Override to show current activity
            }

            $result = [
                'is_currently_in_meeting' => $meetingAttendance?->isCurrentlyInMeeting() ?? false,
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => $attendancePercentage,
                'duration_minutes' => $durationMinutes,  // Now includes current active time!
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'is_late' => $sessionReport?->is_late ?? false,
                'late_minutes' => $sessionReport?->late_minutes ?? 0,
                'last_updated' => $meetingAttendance?->updated_at ?? $sessionReport?->updated_at,
            ];
        }

        Log::info('Final attendance status result', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Create or update session report for a user
     */
    private function createOrUpdateSessionReport(QuranSession $session, User $user): void
    {
        // Check if user is a student using the correct field for this application
        if ($user->user_type !== 'student' && ! $user->studentProfile) {
            return; // Only create reports for students
        }

        $teacher = $session->quranTeacher;
        if (! $teacher) {
            Log::warning('No teacher found for session', ['session_id' => $session->id]);

            return;
        }

        // Create or get existing report
        $report = StudentSessionReport::createOrUpdateReport(
            $session->id,
            $user->id,
            $teacher->id,
            $session->academy_id
        );

        // Sync attendance data immediately
        $this->syncAttendanceToReport($session, $user);
    }

    /**
     * Sync attendance data from MeetingAttendance to StudentSessionReport
     * CRITICAL FIX: Proper sync logic to transfer meeting attendance to reports
     */
    private function syncAttendanceToReport(QuranSession $session, User $user): void
    {
        Log::info('Syncing attendance from MeetingAttendance to StudentSessionReport', [
            'session_id' => $session->id,
            'user_id' => $user->id,
        ]);

        // Get meeting attendance data
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $meetingAttendance) {
            Log::info('No meeting attendance found - creating absent report', [
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

            // Create absent report
            StudentSessionReport::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $user->id,
                ],
                [
                    'attendance_status' => 'absent',
                    'actual_attendance_minutes' => 0,
                    'attendance_percentage' => 0,
                    'join_count' => 0,
                ]
            );

            return;
        }

        // Calculate attendance status based on meeting data
        $totalMinutes = $meetingAttendance->total_duration_minutes;
        $sessionDuration = $session->duration_minutes ?? 60;
        $attendancePercentage = $sessionDuration > 0 ? ($totalMinutes / $sessionDuration) * 100 : 0;

        // Determine status based on attendance
        $attendanceStatus = 'absent';
        if ($totalMinutes > 0) {
            if ($attendancePercentage >= 80) {
                $attendanceStatus = 'present';
            } elseif ($attendancePercentage >= 50) {
                $attendanceStatus = 'partial';
            } else {
                $attendanceStatus = 'partial';
            }
        }

        Log::info('Calculated attendance status', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'total_minutes' => $totalMinutes,
            'session_duration' => $sessionDuration,
            'percentage' => $attendancePercentage,
            'status' => $attendanceStatus,
        ]);

        // Update or create session report
        StudentSessionReport::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $user->id,
            ],
            [
                'attendance_status' => $attendanceStatus,
                'actual_attendance_minutes' => $totalMinutes,
                'attendance_percentage' => round($attendancePercentage, 2),
                'join_count' => $meetingAttendance->join_count,
                'is_late' => false, // TODO: Calculate based on join time
                'late_minutes' => 0,
            ]
        );

        Log::info('Attendance synced successfully', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'final_status' => $attendanceStatus,
        ]);
    }

    /**
     * Override attendance status manually (teacher action)
     */
    public function overrideAttendanceStatus(
        QuranSession $session,
        User $student,
        string $newStatus,
        ?string $reason = null,
        ?User $overriddenBy = null
    ): bool {
        try {
            $report = StudentSessionReport::where('session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            if (! $report) {
                // Create report if it doesn't exist
                $teacher = $session->quranTeacher;
                if ($teacher) {
                    $report = StudentSessionReport::createOrUpdateReport(
                        $session->id,
                        $student->id,
                        $teacher->id,
                        $session->academy_id
                    );
                }
            }

            if ($report) {
                $report->update([
                    'attendance_status' => $newStatus,
                    'manually_overridden' => true,
                    'override_reason' => $reason,
                ]);

                Log::info('Attendance status manually overridden', [
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'old_status' => $report->getOriginal('attendance_status'),
                    'new_status' => $newStatus,
                    'overridden_by' => $overriddenBy?->id,
                    'reason' => $reason,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to override attendance status', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get attendance statistics for a session
     */
    public function getSessionAttendanceStatistics(QuranSession $session): array
    {
        $reports = StudentSessionReport::where('session_id', $session->id)->get();

        $stats = [
            'total_students' => $reports->count(),
            'present' => $reports->where('attendance_status', AttendanceStatus::PRESENT->value)->count(),
            'late' => $reports->where('attendance_status', AttendanceStatus::LATE->value)->count(),
            'partial' => $reports->where('attendance_status', AttendanceStatus::PARTIAL->value)->count(),
            'absent' => $reports->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'average_attendance_percentage' => 0,
            'average_performance' => 0,
        ];

        if ($stats['total_students'] > 0) {
            $stats['average_attendance_percentage'] = $reports->avg('attendance_percentage');
            $evaluatedReports = $reports->whereNotNull('new_memorization_degree');
            $stats['average_performance'] = $evaluatedReports->avg('overall_performance');
        }

        return $stats;
    }

    /**
     * Migrate legacy attendance data to unified system
     */
    public function migrateLegacyAttendanceData(Collection $sessions): array
    {
        $results = [
            'sessions_processed' => 0,
            'reports_created' => 0,
            'reports_updated' => 0,
            'errors' => [],
        ];

        foreach ($sessions as $session) {
            try {
                $results['sessions_processed']++;

                // Process QuranSessionAttendance records
                $legacyAttendances = $session->attendances ?? collect();

                foreach ($legacyAttendances as $attendance) {
                    try {
                        $teacher = $session->quranTeacher;
                        if (! $teacher) {
                            continue;
                        }

                        $report = StudentSessionReport::where('session_id', $session->id)
                            ->where('student_id', $attendance->student_id)
                            ->first();

                        if ($report) {
                            // Update existing report
                            $report->update([
                                'attendance_status' => $attendance->attendance_status,
                                'meeting_enter_time' => $attendance->join_time,
                                'meeting_leave_time' => $attendance->leave_time,
                                'notes' => $attendance->notes,
                                'is_auto_calculated' => false, // Legacy data was manual
                            ]);
                            $results['reports_updated']++;
                        } else {
                            // Create new report
                            StudentSessionReport::create([
                                'session_id' => $session->id,
                                'student_id' => $attendance->student_id,
                                'teacher_id' => $teacher->id,
                                'academy_id' => $session->academy_id,
                                'attendance_status' => $attendance->attendance_status,
                                'meeting_enter_time' => $attendance->join_time,
                                'meeting_leave_time' => $attendance->leave_time,
                                'notes' => $attendance->notes,
                                'is_auto_calculated' => false,
                            ]);
                            $results['reports_created']++;
                        }

                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'session_id' => $session->id,
                            'student_id' => $attendance->student_id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Legacy attendance data migration completed', $results);

        return $results;
    }

    /**
     * Create or update session report for a user (polymorphic)
     */
    private function createOrUpdateSessionReportPolymorphic($session, User $user, string $sessionType): void
    {
        // Check if user is a student
        if ($user->user_type !== 'student' && ! $user->studentProfile) {
            return; // Only create reports for students
        }

        if ($sessionType === 'quran') {
            $this->createOrUpdateQuranSessionReport($session, $user);
        } elseif ($sessionType === 'academic') {
            $this->createOrUpdateAcademicSessionReport($session, $user);
        }
    }

    /**
     * Create or update Quran session report
     */
    private function createOrUpdateQuranSessionReport(QuranSession $session, User $user): void
    {
        $teacher = $session->quranTeacher;
        if (! $teacher) {
            Log::warning('No teacher found for Quran session', ['session_id' => $session->id]);

            return;
        }

        // Create or get existing report
        $report = StudentSessionReport::createOrUpdateReport(
            $session->id,
            $user->id,
            $teacher->id,
            $session->academy_id
        );

        // Sync attendance data immediately (disabled for now due to bugs)
        // $this->syncAttendanceToReport($session, $user);
    }

    /**
     * Create or update Academic session report
     */
    private function createOrUpdateAcademicSessionReport(AcademicSession $session, User $user): void
    {
        $teacher = $session->academicTeacher;
        if (! $teacher) {
            Log::warning('No teacher found for Academic session', ['session_id' => $session->id]);

            return;
        }

        // Create or get existing Academic session report
        $report = AcademicSessionReport::createOrUpdateReport(
            $session->id,
            $user->id,
            $teacher->id,
            $session->academy_id
        );

        // Sync attendance data immediately
        $this->syncAttendanceToAcademicReport($session, $user);
    }

    /**
     * Sync attendance data to report (polymorphic)
     */
    private function syncAttendanceToReportPolymorphic($session, User $user, string $sessionType): void
    {
        if ($sessionType === 'quran') {
            $this->syncAttendanceToReport($session, $user);
        } elseif ($sessionType === 'academic') {
            $this->syncAttendanceToAcademicReport($session, $user);
        }
    }

    /**
     * Sync attendance data from MeetingAttendance to AcademicSessionReport
     */
    private function syncAttendanceToAcademicReport(AcademicSession $session, User $user): void
    {
        Log::info('Syncing attendance to academic session report', [
            'session_id' => $session->id,
            'user_id' => $user->id,
        ]);

        $report = AcademicSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();

        if ($report) {
            $report->syncFromMeetingAttendance();
        }
    }
}
