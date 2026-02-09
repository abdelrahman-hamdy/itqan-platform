<?php

namespace App\Services;

use App\Contracts\MeetingCapable;
use App\Enums\AttendanceStatus;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Attendance Calculation Service
 *
 * Handles pure attendance calculation logic including:
 * - Join/leave event tracking
 * - Final attendance percentage calculation
 * - Reconnection detection
 * - Attendance statistics generation
 * - Data export for reporting
 */
class AttendanceCalculationService
{
    /**
     * Handle user joining a meeting
     */
    public function handleUserJoin(MeetingCapable $session, User $user): ?MeetingAttendance
    {
        try {
            // Create or get existing attendance record
            $attendance = MeetingAttendance::findOrCreateForUser($session, $user);

            // Record the join event
            $joinSuccess = $attendance->recordJoin();

            if (! $joinSuccess) {
                return null;
            }

            Log::info('User joined meeting successfully', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => $attendance->user_type,
                'join_count' => $attendance->join_count,
            ]);

            return $attendance;

        } catch (\Exception $e) {
            Log::error('Failed to handle user join', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle user leaving a meeting
     */
    public function handleUserLeave(MeetingCapable $session, User $user): ?MeetingAttendance
    {
        try {
            // Find existing attendance record
            $attendance = $session->meetingAttendances()
                ->where('user_id', $user->id)
                ->first();

            if (! $attendance) {
                Log::warning('User tried to leave meeting but no attendance record found', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ]);

                return null;
            }

            // Record the leave event
            $leaveSuccess = $attendance->recordLeave();

            if (! $leaveSuccess) {
                return null;
            }

            Log::info('User left meeting successfully', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'leave_count' => $attendance->leave_count,
                'total_duration' => $attendance->total_duration_minutes,
            ]);

            return $attendance;

        } catch (\Exception $e) {
            Log::error('Failed to handle user leave', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle user joining a meeting (Polymorphic version for any session type)
     */
    public function handleUserJoinPolymorphic($session, User $user, string $sessionType): ?MeetingAttendance
    {
        try {
            // Create or get existing attendance record
            $attendance = MeetingAttendance::findOrCreateForUserPolymorphic($session, $user, $sessionType);

            // Record the join event
            $joinSuccess = $attendance->recordJoin();

            if (! $joinSuccess) {
                return null;
            }

            Log::info('User joined meeting successfully (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'user_type' => $attendance->user_type,
                'join_count' => $attendance->join_count,
            ]);

            return $attendance;

        } catch (\Exception $e) {
            Log::error('Failed to handle user join (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle user leaving a meeting (Polymorphic version for any session type)
     */
    public function handleUserLeavePolymorphic($session, User $user, string $sessionType): ?MeetingAttendance
    {
        try {
            // Map the polymorphic session type to the database enum values
            $dbSessionType = match ($sessionType) {
                'academic' => 'academic',
                'quran' => $session->session_type ?? 'group',
                default => $session->session_type ?? 'group',
            };

            // Find existing attendance record
            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->where('session_type', $dbSessionType)
                ->first();

            if (! $attendance) {
                Log::warning('User tried to leave meeting but no attendance record found (polymorphic)', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'session_type' => $sessionType,
                    'db_session_type' => $dbSessionType,
                ]);

                return null;
            }

            // Record the leave event
            $leaveSuccess = $attendance->recordLeave();

            if (! $leaveSuccess) {
                return null;
            }

            Log::info('User left meeting successfully (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'leave_count' => $attendance->leave_count,
                'total_duration' => $attendance->total_duration_minutes,
            ]);

            return $attendance;

        } catch (\Exception $e) {
            Log::error('Failed to handle user leave (polymorphic)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Calculate final attendance for all participants of a session
     */
    public function calculateFinalAttendance(MeetingCapable $session): array
    {
        $results = [
            'session_id' => $session->id,
            'calculated_count' => 0,
            'errors' => [],
            'attendances' => [],
        ];

        try {
            // Get all meeting attendances for this session
            $attendances = $session->meetingAttendances()
                ->where('is_calculated', false)
                ->get();

            foreach ($attendances as $attendance) {
                try {
                    if ($attendance->calculateFinalAttendance()) {
                        $results['calculated_count']++;
                        $results['attendances'][] = [
                            'user_id' => $attendance->user_id,
                            'attendance_status' => $attendance->attendance_status,
                            'attendance_percentage' => $attendance->attendance_percentage,
                            'total_duration' => $attendance->total_duration_minutes,
                        ];
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'user_id' => $attendance->user_id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Update session participants count
            $session->update([
                'participants_count' => $session->meetingAttendances()->count(),
            ]);

            Log::info('Final attendance calculated for session', [
                'session_id' => $session->id,
                'calculated_count' => $results['calculated_count'],
                'total_attendances' => $attendances->count(),
            ]);

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];

            Log::error('Failed to calculate final attendance for session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Handle reconnection detection
     * If user rejoins within 2 minutes, treat as same session
     */
    public function handleReconnection(MeetingCapable $session, User $user): bool
    {
        $attendance = $session->meetingAttendances()
            ->where('user_id', $user->id)
            ->first();

        if (! $attendance || ! $attendance->last_leave_time) {
            return false; // Not a reconnection
        }

        $timeSinceLeave = $attendance->last_leave_time->diffInSeconds(now());
        $reconnectionThreshold = config('business.attendance.reconnection_threshold_seconds', 120);

        if ($timeSinceLeave <= $reconnectionThreshold) {
            // This is a reconnection - merge with last cycle
            $cycles = $attendance->join_leave_cycles ?? [];
            $lastCycleIndex = count($cycles) - 1;

            if ($lastCycleIndex >= 0 && isset($cycles[$lastCycleIndex]['left_at'])) {
                // Remove the leave time from last cycle to merge sessions
                unset($cycles[$lastCycleIndex]['left_at']);
                unset($cycles[$lastCycleIndex]['duration_minutes']);

                $attendance->update([
                    'join_leave_cycles' => $cycles,
                    'last_leave_time' => null,
                    'leave_count' => max(0, $attendance->leave_count - 1),
                ]);

                Log::info('Reconnection detected and merged', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'time_since_leave' => $timeSinceLeave,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Get attendance statistics for a session
     */
    public function getAttendanceStatistics(MeetingCapable $session): array
    {
        $attendances = $session->meetingAttendances()->calculated()->get();

        $stats = [
            'total_participants' => $attendances->count(),
            'present' => $attendances->where('attendance_status', AttendanceStatus::ATTENDED->value)->count(),
            'late' => $attendances->where('attendance_status', AttendanceStatus::LATE->value)->count(),
            'partial' => $attendances->where('attendance_status', AttendanceStatus::LEFT->value)->count(),
            'absent' => $attendances->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'average_attendance_percentage' => 0,
            'total_meeting_duration' => 0,
        ];

        if ($stats['total_participants'] > 0) {
            $stats['average_attendance_percentage'] = $attendances->avg('attendance_percentage');
            $stats['total_meeting_duration'] = $attendances->sum('total_duration_minutes');
        }

        return $stats;
    }

    /**
     * Cleanup old uncalculated attendance records
     */
    public function cleanupOldAttendanceRecords(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $cleaned = MeetingAttendance::where('is_calculated', false)
            ->where('created_at', '<', $cutoffDate)
            ->whereHas('session', function ($query) use ($cutoffDate) {
                $query->where('scheduled_at', '<', $cutoffDate)
                    ->final();
            })
            ->delete();

        if ($cleaned > 0) {
            Log::info('Cleaned up old uncalculated attendance records', [
                'records_deleted' => $cleaned,
                'cutoff_date' => $cutoffDate,
            ]);
        }

        return $cleaned;
    }

    /**
     * Force recalculation of attendance for a session
     */
    public function recalculateAttendance(MeetingCapable $session): array
    {
        // Reset calculation status
        $session->meetingAttendances()->update([
            'is_calculated' => false,
            'attendance_calculated_at' => null,
        ]);

        // Recalculate
        return $this->calculateFinalAttendance($session);
    }

    /**
     * Export attendance data for reporting
     */
    public function exportAttendanceData(MeetingCapable $session): array
    {
        $attendances = $session->meetingAttendances()->calculated()->get();

        return $attendances->map(function ($attendance) {
            return [
                'user_id' => $attendance->user_id,
                'user_name' => $attendance->user?->name ?? 'Unknown',
                'user_type' => $attendance->user_type,
                'first_join_time' => $attendance->first_join_time?->toISOString(),
                'last_leave_time' => $attendance->last_leave_time?->toISOString(),
                'total_duration_minutes' => $attendance->total_duration_minutes,
                'attendance_status' => $attendance->attendance_status,
                'attendance_percentage' => $attendance->attendance_percentage,
                'join_count' => $attendance->join_count,
                'leave_count' => $attendance->leave_count,
                'join_leave_cycles' => $attendance->join_leave_cycles,
            ];
        })->toArray();
    }

    /**
     * Process attendance for multiple completed sessions
     */
    public function processCompletedSessions(Collection $sessions): array
    {
        $results = [
            'processed_sessions' => 0,
            'total_attendances_calculated' => 0,
            'errors' => [],
        ];

        foreach ($sessions as $session) {
            try {
                $sessionResults = $this->calculateFinalAttendance($session);
                $results['processed_sessions']++;
                $results['total_attendances_calculated'] += $sessionResults['calculated_count'];

                if (! empty($sessionResults['errors'])) {
                    $results['errors'][$session->id] = $sessionResults['errors'];
                }

            } catch (\Exception $e) {
                $results['errors'][$session->id] = [$e->getMessage()];
                Log::error('Failed to process completed session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
