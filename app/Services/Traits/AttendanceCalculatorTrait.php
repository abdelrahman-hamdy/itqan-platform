<?php

namespace App\Services\Traits;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;

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
     * @return string One of: 'attended', 'late', 'left', 'absent'
     */
    protected function calculateAttendanceStatus(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        ?int $graceMinutes = null
    ): string {
        $graceMinutes = $graceMinutes ?? config('business.attendance.grace_period_minutes', 15);
        // If never joined, definitely absent
        if (! $firstJoinTime) {
            return AttendanceStatus::ABSENT->value;
        }

        // Calculate attendance percentage
        $attendancePercentage = $sessionDurationMinutes > 0
            ? ($actualAttendanceMinutes / $sessionDurationMinutes) * 100
            : 0;

        // Get "left early" threshold from config (default 50%)
        $leftEarlyThreshold = config('business.attendance.minimum_presence_percent', 50);

        // Stayed less than minimum threshold - left early (regardless of join time)
        if ($attendancePercentage < $leftEarlyThreshold) {
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
     */
    protected function calculateAttendanceStatusEnum(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        ?int $graceMinutes = null
    ): \App\Enums\AttendanceStatus {
        $graceMinutes = $graceMinutes ?? config('business.attendance.grace_period_minutes', 15);
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
     * @param  int  $currentAttendanceMinutes  Real-time attendance including current cycle
     * @param  bool  $isCurrentlyInMeeting  Whether user is still in meeting
     */
    protected function calculateRealtimeAttendanceStatus(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $currentAttendanceMinutes,
        ?int $graceMinutes = null,
        bool $isCurrentlyInMeeting = false
    ): string {
        $graceMinutes = $graceMinutes ?? config('business.attendance.grace_period_minutes', 15);

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

        // Get configurable thresholds
        $excellentPercent = config('business.attendance.excellent_percent', 95);
        $thresholdPercent = config('business.attendance.threshold_percent', 80);
        $leftThresholdPercent = config('business.attendance.left_threshold_percent', 30);

        // If joined after grace time, check attendance status
        if (! $joinedWithinGrace) {
            // CRITICAL FIX: If user is currently in meeting, they're actively attending
            // Don't apply final thresholds to real-time data - show "late" not "absent"
            if ($isCurrentlyInMeeting) {
                return AttendanceStatus::LATE->value; // Late but actively attending
            }

            // Session ended or user left - apply final thresholds
            if ($attendancePercentage >= $excellentPercent) {
                return AttendanceStatus::LATE->value; // Late arrival but excellent attendance
            } elseif ($attendancePercentage >= $thresholdPercent) {
                return AttendanceStatus::LEFT->value; // Late and decent attendance
            } else {
                return AttendanceStatus::ABSENT->value; // Late and poor attendance
            }
        }

        // Joined on time - check attendance status
        // CRITICAL FIX: If user is currently in meeting, they're actively attending
        if ($isCurrentlyInMeeting) {
            return AttendanceStatus::ATTENDED->value; // On time and actively attending
        }

        // Session ended or user left - apply final thresholds
        if ($attendancePercentage >= $thresholdPercent) {
            return AttendanceStatus::ATTENDED->value;
        } elseif ($attendancePercentage >= $leftThresholdPercent) {
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
     * @return float Percentage (0-100, capped at 100)
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
     */
    protected function isLateJoin(?Carbon $joinTime, Carbon $sessionStartTime, ?int $graceMinutes = null): bool
    {
        $graceMinutes = $graceMinutes ?? config('business.attendance.grace_period_minutes', 15);

        if (! $joinTime) {
            return false;
        }

        $graceThreshold = $sessionStartTime->copy()->addMinutes($graceMinutes);

        return $joinTime->isAfter($graceThreshold);
    }

    /**
     * Calculate late minutes.
     *
     * @return int Minutes late (0 if on time or early)
     */
    protected function calculateLateMinutes(?Carbon $joinTime, Carbon $sessionStartTime, ?int $graceMinutes = null): int
    {
        $graceMinutes = $graceMinutes ?? config('business.attendance.grace_period_minutes', 15);

        if (! $joinTime || ! $this->isLateJoin($joinTime, $sessionStartTime, $graceMinutes)) {
            return 0;
        }

        return $joinTime->diffInMinutes($sessionStartTime);
    }
}
