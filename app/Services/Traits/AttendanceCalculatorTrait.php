<?php

namespace App\Services\Traits;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;

/**
 * Centralized percentage-based attendance calculation.
 *
 * Status rules:
 *   attended% >= fullPercent     → ATTENDED
 *   attended% >= partialPercent  → PARTIALLY_ATTENDED
 *   attended% <  partialPercent  → ABSENT
 *   no join                      → ABSENT
 *
 * Both student and teacher share the same shape and differ only in the
 * thresholds the caller passes in.
 */
trait AttendanceCalculatorTrait
{
    /**
     * Calculate attendance status from attended duration as a percentage of
     * the scheduled session window.
     *
     * @param  Carbon|null  $firstJoinTime  When the user first joined (null → ABSENT)
     * @param  int  $sessionDurationMinutes  Scheduled session duration (denominator)
     * @param  int  $actualAttendanceMinutes  Time within the scheduled window
     * @param  float  $fullPercent  Threshold for ATTENDED (inclusive)
     * @param  float  $partialPercent  Threshold for PARTIALLY_ATTENDED (inclusive)
     * @return string AttendanceStatus value: 'attended' | 'partially_attended' | 'absent'
     */
    protected function calculateAttendanceStatus(
        ?Carbon $firstJoinTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        float $fullPercent,
        float $partialPercent,
    ): string {
        if (! $firstJoinTime) {
            return AttendanceStatus::ABSENT->value;
        }

        if ($sessionDurationMinutes <= 0) {
            return AttendanceStatus::ABSENT->value;
        }

        $attendancePercentage = ($actualAttendanceMinutes / $sessionDurationMinutes) * 100;

        if ($attendancePercentage >= $fullPercent) {
            return AttendanceStatus::ATTENDED->value;
        }

        if ($attendancePercentage >= $partialPercent) {
            return AttendanceStatus::PARTIALLY_ATTENDED->value;
        }

        return AttendanceStatus::ABSENT->value;
    }

    /**
     * Teacher-specific wrapper. Identical logic to calculateAttendanceStatus();
     * kept as a distinct method so callers express intent at the call site
     * ("I am calculating a teacher's status with teacher thresholds").
     */
    protected function calculateTeacherAttendanceStatus(
        ?Carbon $firstJoinTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        float $fullPercent = 90.0,
        float $partialPercent = 50.0,
    ): string {
        return $this->calculateAttendanceStatus(
            $firstJoinTime,
            $sessionDurationMinutes,
            $actualAttendanceMinutes,
            $fullPercent,
            $partialPercent,
        );
    }

    /**
     * Enum-returning wrapper around calculateAttendanceStatus().
     */
    protected function calculateAttendanceStatusEnum(
        ?Carbon $firstJoinTime,
        int $sessionDurationMinutes,
        int $actualAttendanceMinutes,
        float $fullPercent,
        float $partialPercent,
    ): AttendanceStatus {
        return AttendanceStatus::from(
            $this->calculateAttendanceStatus(
                $firstJoinTime,
                $sessionDurationMinutes,
                $actualAttendanceMinutes,
                $fullPercent,
                $partialPercent,
            )
        );
    }

    /**
     * Real-time attendance status for users currently in an active session.
     *
     * When the user is still in the meeting and hasn't yet crossed the partial
     * threshold, we return PARTIALLY_ATTENDED optimistically so the UI doesn't
     * flip-flop to ABSENT mid-session. Once they actually leave, the final
     * calc job uses calculateAttendanceStatus() and writes the definitive value.
     */
    protected function calculateRealtimeAttendanceStatus(
        ?Carbon $firstJoinTime,
        int $sessionDurationMinutes,
        int $currentAttendanceMinutes,
        float $fullPercent,
        float $partialPercent,
        bool $isCurrentlyInMeeting = false,
    ): string {
        if (! $firstJoinTime) {
            return AttendanceStatus::ABSENT->value;
        }

        if ($sessionDurationMinutes <= 0) {
            return AttendanceStatus::ABSENT->value;
        }

        $attendancePercentage = ($currentAttendanceMinutes / $sessionDurationMinutes) * 100;

        if ($attendancePercentage >= $fullPercent) {
            return AttendanceStatus::ATTENDED->value;
        }

        if ($attendancePercentage >= $partialPercent) {
            return AttendanceStatus::PARTIALLY_ATTENDED->value;
        }

        // Below partial threshold — if they're still in the meeting, show as
        // PARTIALLY_ATTENDED so the UI doesn't read "absent" for someone who's
        // visibly on screen. If they've already left, honor the hard cutoff.
        if ($isCurrentlyInMeeting) {
            return AttendanceStatus::PARTIALLY_ATTENDED->value;
        }

        return AttendanceStatus::ABSENT->value;
    }

    /**
     * Calculate attendance percentage (capped at 100).
     */
    protected function calculateAttendancePercentage(int $actualMinutes, int $sessionDurationMinutes): float
    {
        if ($sessionDurationMinutes <= 0) {
            return 0.0;
        }

        return min(100.0, ($actualMinutes / $sessionDurationMinutes) * 100);
    }

    /**
     * Sum minutes from join/leave cycles that fall inside [windowStart, windowEnd].
     *
     * Used by `display_duration_minutes` which counts time inside the full
     * meeting window (prep + session + buffer), and — with a tighter window —
     * could also be used for the scheduled-window calculation duration.
     *
     * Supports both webhook format (`type`/`timestamp`) and manual format
     * (`joined_at`/`left_at`) used by MeetingAttendance::recordJoin/Leave.
     */
    protected function sumCycleMinutesInWindow(array $cycles, Carbon $windowStart, Carbon $windowEnd): int
    {
        $total = 0;
        $lastJoinTime = null;

        foreach ($cycles as $cycle) {
            if (isset($cycle['type'])) {
                if ($cycle['type'] === 'join') {
                    $lastJoinTime = $cycle['timestamp'];
                } elseif ($cycle['type'] === 'leave' && $lastJoinTime) {
                    $join = is_string($lastJoinTime) ? Carbon::parse($lastJoinTime) : $lastJoinTime;
                    $leave = is_string($cycle['timestamp']) ? Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];
                    $total += $this->clampCycleMinutes($join, $leave, $windowStart, $windowEnd);
                    $lastJoinTime = null;
                }
            } elseif (isset($cycle['joined_at'], $cycle['left_at'])) {
                $join = is_string($cycle['joined_at']) ? Carbon::parse($cycle['joined_at']) : $cycle['joined_at'];
                $leave = is_string($cycle['left_at']) ? Carbon::parse($cycle['left_at']) : $cycle['left_at'];
                $total += $this->clampCycleMinutes($join, $leave, $windowStart, $windowEnd);
            }
        }

        return $total;
    }

    /**
     * Clamp a single join/leave pair to [windowStart, windowEnd] and return
     * the duration in whole minutes. Returns 0 if the pair doesn't overlap.
     */
    protected function clampCycleMinutes(Carbon $join, Carbon $leave, Carbon $windowStart, Carbon $windowEnd): int
    {
        if ($join->lt($windowStart)) {
            $join = $windowStart->copy();
        }
        if ($leave->gt($windowEnd)) {
            $leave = $windowEnd->copy();
        }
        if ($leave->lte($join)) {
            return 0;
        }

        return (int) round($join->diffInMinutes($leave));
    }
}
