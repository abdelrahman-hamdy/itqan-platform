<?php

namespace App\Services\Traits;

use Carbon\Carbon;

/**
 * Trait AttendanceCalculatorTrait
 *
 * Provides centralized attendance status calculation logic.
 * Eliminates duplication across BaseSessionAttendance, BaseSessionReport, and MeetingAttendance.
 *
 * Attendance Rules (50% threshold):
 * - < 50% attendance = 'leaved' (left early)
 * - >= 50% attendance + joined after grace = 'late'
 * - >= 50% attendance + joined on time = 'attended'
 * - No join = 'absent'
 */
trait AttendanceCalculatorTrait
{
    /**
     * Calculate attendance status based on join time, duration, and thresholds.
     *
     * This is the single source of truth for attendance calculation.
     *
     * @param  Carbon|null  $firstJoinTime  When user first joined
     * @param  Carbon  $sessionStartTime  When session was scheduled to start
     * @param  int  $sessionDurationMinutes  Total session duration
     * @param  int  $actualAttendanceMinutes  How long user actually attended
     * @param  int  $graceMinutes  Grace period for late arrivals (default 15)
     * @return string  One of: 'attended', 'late', 'leaved', 'absent'
     */
    protected function calculateAttendanceStatus(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        int $graceMinutes = 15
    ): string {
        // If never joined, definitely absent
        if (! $firstJoinTime) {
            return 'absent';
        }

        // Calculate attendance percentage
        $attendancePercentage = $sessionDurationMinutes > 0
            ? ($actualAttendanceMinutes / $sessionDurationMinutes) * 100
            : 0;

        // Stayed < 50% - left early (regardless of join time)
        if ($attendancePercentage < 50) {
            return 'leaved';
        }

        // Stayed >= 50% - check if late
        $lateThreshold = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $wasLate = $firstJoinTime->isAfter($lateThreshold);

        // Stayed >= 50% and joined after tolerance - late
        if ($wasLate) {
            return 'late';
        }

        // Stayed >= 50% and joined on time - attended
        return 'attended';
    }

    /**
     * Calculate attendance status using the AttendanceStatus enum.
     *
     * @param  Carbon|null  $firstJoinTime
     * @param  Carbon  $sessionStartTime
     * @param  int  $sessionDurationMinutes
     * @param  int  $actualAttendanceMinutes
     * @param  int  $graceMinutes
     * @return \App\Enums\AttendanceStatus
     */
    protected function calculateAttendanceStatusEnum(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        int $graceMinutes = 15
    ): \App\Enums\AttendanceStatus {
        $status = $this->calculateAttendanceStatus(
            $firstJoinTime,
            $sessionStartTime,
            $sessionDurationMinutes,
            $actualAttendanceMinutes,
            $graceMinutes
        );

        return \App\Enums\AttendanceStatus::from($status);
    }

    /**
     * Calculate real-time attendance status for active sessions.
     * Handles edge cases for students who join early or stay long.
     *
     * @param  Carbon|null  $firstJoinTime
     * @param  Carbon  $sessionStartTime
     * @param  int  $sessionDurationMinutes
     * @param  int  $currentAttendanceMinutes  Real-time attendance including current cycle
     * @param  int  $graceMinutes
     * @param  bool  $isCurrentlyInMeeting  Whether user is still in meeting
     * @return string
     */
    protected function calculateRealtimeAttendanceStatus(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $currentAttendanceMinutes,
        int $graceMinutes = 15,
        bool $isCurrentlyInMeeting = false
    ): string {
        // No join = absent
        if (! $firstJoinTime) {
            return 'absent';
        }

        // Calculate attendance percentage
        $attendancePercentage = $sessionDurationMinutes > 0
            ? ($currentAttendanceMinutes / $sessionDurationMinutes) * 100
            : 0;

        // 100% attendance override - anyone who attended full session is marked attended
        if ($attendancePercentage >= 100) {
            return 'attended';
        }

        // Check if joined within grace period
        $graceThresholdTime = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $joinedWithinGrace = $firstJoinTime->lte($graceThresholdTime);

        // If joined after grace time, check if they made up for it
        if (! $joinedWithinGrace) {
            if ($attendancePercentage >= 95) {
                return 'late'; // Late arrival but excellent attendance
            } elseif ($attendancePercentage >= 80) {
                return 'leaved'; // Late and decent attendance
            } else {
                return 'absent'; // Late and poor attendance
            }
        }

        // Joined on time - standard percentage rules
        if ($attendancePercentage >= 80) {
            return 'attended';
        } elseif ($attendancePercentage >= 30) {
            return 'leaved';
        } else {
            return 'absent';
        }
    }

    /**
     * Calculate attendance percentage.
     *
     * @param  int  $actualMinutes  Minutes attended
     * @param  int  $sessionDurationMinutes  Total session duration
     * @return float  Percentage (0-100, capped at 100)
     */
    protected function calculateAttendancePercentage(int $actualMinutes, int $sessionDurationMinutes): float
    {
        if ($sessionDurationMinutes <= 0) {
            return 0.0;
        }

        return min(100.0, ($actualMinutes / $sessionDurationMinutes) * 100);
    }

    /**
     * Determine if a student is late based on join time and session start.
     *
     * @param  Carbon|null  $joinTime
     * @param  Carbon  $sessionStartTime
     * @param  int  $graceMinutes
     * @return bool
     */
    protected function isLateJoin(?Carbon $joinTime, Carbon $sessionStartTime, int $graceMinutes = 15): bool
    {
        if (! $joinTime) {
            return false;
        }

        $graceThreshold = $sessionStartTime->copy()->addMinutes($graceMinutes);

        return $joinTime->isAfter($graceThreshold);
    }

    /**
     * Calculate late minutes.
     *
     * @param  Carbon|null  $joinTime
     * @param  Carbon  $sessionStartTime
     * @param  int  $graceMinutes
     * @return int  Minutes late (0 if on time or early)
     */
    protected function calculateLateMinutes(?Carbon $joinTime, Carbon $sessionStartTime, int $graceMinutes = 15): int
    {
        if (! $joinTime || ! $this->isLateJoin($joinTime, $sessionStartTime, $graceMinutes)) {
            return 0;
        }

        return $joinTime->diffInMinutes($sessionStartTime);
    }
}
