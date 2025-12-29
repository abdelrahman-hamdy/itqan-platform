<?php

namespace App\Services\Traits;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use App\Enums\SessionStatus;

/**
 * Trait AttendanceCalculatorTrait
 *
 * Provides centralized attendance status calculation logic.
 * Eliminates duplication across BaseSessionAttendance, BaseSessionReport, and MeetingAttendance.
 *
 * Attendance Rules (50% threshold):
 * - < 50% attendance = 'left' (left early)
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
     * @return string  One of: 'attended', 'late', 'left', 'absent'
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
            return AttendanceStatus::ABSENT->value;
        }

        // Calculate attendance percentage
        $attendancePercentage = $sessionDurationMinutes > 0
            ? ($actualAttendanceMinutes / $sessionDurationMinutes) * 100
            : 0;

        // Stayed < 50% - left early (regardless of join time)
        if ($attendancePercentage < 50) {
            return AttendanceStatus::LEFT->value;
        }

        // Stayed >= 50% - check if late
        $lateThreshold = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $wasLate = $firstJoinTime->isAfter($lateThreshold);

        // Stayed >= 50% and joined after tolerance - late
        if ($wasLate) {
            return AttendanceStatus::LATE->value;
        }

        // Stayed >= 50% and joined on time - attended
        return AttendanceStatus::ATTENDED->value;
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
            return AttendanceStatus::ABSENT->value;
        }

        // Calculate attendance percentage
        $attendancePercentage = $sessionDurationMinutes > 0
            ? ($currentAttendanceMinutes / $sessionDurationMinutes) * 100
            : 0;

        // 100% attendance override - anyone who attended full session is marked attended
        if ($attendancePercentage >= 100) {
            return AttendanceStatus::ATTENDED->value;
        }

        // Check if joined within grace period
        $graceThresholdTime = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $joinedWithinGrace = $firstJoinTime->lte($graceThresholdTime);

        // If joined after grace time, check if they made up for it
        if (! $joinedWithinGrace) {
            if ($attendancePercentage >= 95) {
                return AttendanceStatus::LATE->value; // Late arrival but excellent attendance
            } elseif ($attendancePercentage >= 80) {
                return AttendanceStatus::LEFT->value; // Late and decent attendance
            } else {
                return AttendanceStatus::ABSENT->value; // Late and poor attendance
            }
        }

        // Joined on time - standard percentage rules
        if ($attendancePercentage >= 80) {
            return AttendanceStatus::ATTENDED->value;
        } elseif ($attendancePercentage >= 30) {
            return AttendanceStatus::LEFT->value;
        } else {
            return AttendanceStatus::ABSENT->value;
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
