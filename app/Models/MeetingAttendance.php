<?php

namespace App\Models;

use App\Contracts\MeetingCapable;
use App\Enums\AttendanceStatus;
use App\Services\AcademyContextService;
use App\Services\Traits\AttendanceCalculatorTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * MeetingAttendance Model
 *
 * Tracks attendance for LiveKit meetings across all session types.
 *
 * @property int $id
 * @property int $session_id
 * @property int $user_id
 * @property string|null $user_type
 * @property string $session_type
 * @property \Carbon\Carbon|null $first_join_time
 * @property \Carbon\Carbon|null $last_leave_time
 * @property \Carbon\Carbon|null $last_heartbeat_at
 * @property int|null $total_duration_minutes
 * @property array|null $join_leave_cycles
 * @property \Carbon\Carbon|null $attendance_calculated_at
 * @property string|null $attendance_status
 * @property float|null $attendance_percentage
 * @property int|null $session_duration_minutes
 * @property \Carbon\Carbon|null $session_start_time
 * @property \Carbon\Carbon|null $session_end_time
 * @property int|null $join_count
 * @property int|null $leave_count
 * @property bool $is_calculated
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MeetingAttendance extends Model
{
    use AttendanceCalculatorTrait;
    use HasFactory;

    /**
     * Post-session grace period in minutes.
     * This is the overtime allowance after session scheduled end time.
     * Used for calculating when a session has truly ended for attendance purposes.
     */
    public const POST_SESSION_GRACE_MINUTES = 30;

    /**
     * Default late tolerance in minutes (fallback if academy settings not available).
     * Used for determining if a participant joined "late".
     */
    public const DEFAULT_LATE_TOLERANCE_MINUTES = 15;

    protected $fillable = [
        'session_id',
        'user_id',
        'user_type',
        'session_type',
        'first_join_time',
        'last_leave_time',
        'last_heartbeat_at',
        'total_duration_minutes',
        'join_leave_cycles',
        'attendance_calculated_at',
        'attendance_status',
        'attendance_percentage',
        'session_duration_minutes',
        'session_start_time',
        'session_end_time',
        'join_count',
        'leave_count',
        'is_calculated',
    ];

    protected $casts = [
        'first_join_time' => 'datetime',
        'last_leave_time' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'join_leave_cycles' => 'array',
        'attendance_calculated_at' => 'datetime',
        'attendance_status' => AttendanceStatus::class,
        'session_start_time' => 'datetime',
        'session_end_time' => 'datetime',
        'attendance_percentage' => 'decimal:2',
        'total_duration_minutes' => 'integer',
        'session_duration_minutes' => 'integer',
        'join_count' => 'integer',
        'leave_count' => 'integer',
        'is_calculated' => 'boolean',
    ];

    /**
     * Polymorphic relationship with session (QuranSession, AcademicSession, or InteractiveCourseSession)
     */
    public function session(): BelongsTo
    {
        // Check session_type to determine which model to use
        if ($this->session_type === 'academic') {
            return $this->belongsTo(AcademicSession::class, 'session_id');
        }

        if ($this->session_type === 'interactive') {
            return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
        }

        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * Specifically get QuranSession
     */
    public function quranSession(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * Specifically get AcademicSession
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Specifically get InteractiveCourseSession
     */
    public function interactiveSession(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Record a user joining the meeting
     */
    public function recordJoin(): bool
    {
        $now = AcademyContextService::nowInAcademyTimezone();
        $cycles = $this->join_leave_cycles ?? [];

        // Check if user is already in the meeting (has joined but not left)
        $lastCycle = end($cycles);
        if ($lastCycle && isset($lastCycle['joined_at']) && ! isset($lastCycle['left_at'])) {
            Log::info('User already in meeting, updating join status', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
                'last_cycle' => $lastCycle,
            ]);

            // Return true since user is already in meeting (this is success)
            return true;
        }

        // Add new join event
        $cycles[] = [
            'joined_at' => $now->toISOString(),
            'left_at' => null,
        ];

        $this->update([
            'first_join_time' => $this->first_join_time ?? $now,
            'join_leave_cycles' => $cycles,
            'join_count' => $this->join_count + 1,
        ]);

        Log::info('User joined meeting', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'join_time' => $now,
            'join_count' => $this->join_count,
            'is_currently_in_meeting' => $this->isCurrentlyInMeeting(),
        ]);

        return true;
    }

    /**
     * Record a user leaving the meeting
     */
    public function recordLeave(): bool
    {
        $now = AcademyContextService::nowInAcademyTimezone();
        $cycles = $this->join_leave_cycles ?? [];

        // Find the last open cycle (joined but not left)
        $lastCycleIndex = null;
        for ($i = count($cycles) - 1; $i >= 0; $i--) {
            if (isset($cycles[$i]['joined_at']) && ! isset($cycles[$i]['left_at'])) {
                $lastCycleIndex = $i;
                break;
            }
        }

        if ($lastCycleIndex === null) {
            Log::warning('User tried to leave meeting but not currently in meeting', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
            ]);

            return false;
        }

        // Update the last cycle with leave time
        $cycles[$lastCycleIndex]['left_at'] = $now->toISOString();

        // Calculate duration for this cycle (capped at session start if joined during preparation)
        $joinTime = Carbon::parse($cycles[$lastCycleIndex]['joined_at']);
        $session = $this->session;
        $effectiveJoinTime = $joinTime;

        // Cap at session start if user joined during preparation time
        if ($session && $session->scheduled_at && $joinTime->lessThan($session->scheduled_at)) {
            $effectiveJoinTime = $session->scheduled_at;
            Log::debug('Leave: Capping cycle duration at session start', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
                'actual_join' => $joinTime->toISOString(),
                'effective_join' => $effectiveJoinTime->toISOString(),
                'session_start' => $session->scheduled_at->toISOString(),
            ]);
        }

        $cycleDurationMinutes = $effectiveJoinTime->diffInMinutes($now);
        $cycles[$lastCycleIndex]['duration_minutes'] = $cycleDurationMinutes;

        $this->update([
            'last_leave_time' => $now,
            'join_leave_cycles' => $cycles,
            'leave_count' => $this->leave_count + 1,
            'total_duration_minutes' => $this->calculateTotalDuration($cycles),
        ]);

        Log::info('User left meeting', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'leave_time' => $now,
            'actual_join_time' => $joinTime->toISOString(),
            'effective_join_time' => $effectiveJoinTime->toISOString(),
            'cycle_duration' => $cycleDurationMinutes,
            'total_duration' => $this->total_duration_minutes,
        ]);

        return true;
    }

    /**
     * Calculate total duration from all cycles
     */
    private function calculateTotalDuration(array $cycles): int
    {
        $totalMinutes = 0;
        $session = $this->session;

        foreach ($cycles as $cycle) {
            if (isset($cycle['joined_at']) && isset($cycle['left_at'])) {
                $joinTime = Carbon::parse($cycle['joined_at']);
                $leaveTime = Carbon::parse($cycle['left_at']);

                // CRITICAL FIX: Only count attendance from session start time, not preparation time
                // If user joined before session started, cap the effective join time at session start
                if ($session && $session->scheduled_at) {
                    $sessionStart = $session->scheduled_at;

                    // If joined before session started, use session start as effective join time
                    if ($joinTime->lessThan($sessionStart)) {
                        // Only count if they stayed past session start
                        if ($leaveTime->greaterThan($sessionStart)) {
                            $effectiveJoinTime = $sessionStart;
                            $duration = $effectiveJoinTime->diffInMinutes($leaveTime);
                            $totalMinutes += $duration;

                            Log::debug('Attendance capped at session start time', [
                                'session_id' => $this->session_id,
                                'user_id' => $this->user_id,
                                'actual_join' => $joinTime->toISOString(),
                                'session_start' => $sessionStart->toISOString(),
                                'effective_join' => $effectiveJoinTime->toISOString(),
                                'leave_time' => $leaveTime->toISOString(),
                                'duration_minutes' => $duration,
                            ]);
                        } else {
                            // User joined and left before session even started - don't count
                            Log::debug('Attendance not counted - joined and left before session start', [
                                'session_id' => $this->session_id,
                                'user_id' => $this->user_id,
                                'join_time' => $joinTime->toISOString(),
                                'leave_time' => $leaveTime->toISOString(),
                                'session_start' => $sessionStart->toISOString(),
                            ]);
                        }
                    } else {
                        // Joined after session started - normal calculation
                        $totalMinutes += $joinTime->diffInMinutes($leaveTime);
                    }
                } else {
                    // No session context - fallback to normal calculation
                    $totalMinutes += $joinTime->diffInMinutes($leaveTime);
                }
            }
        }

        return $totalMinutes;
    }

    /**
     * Calculate final attendance after session ends
     */
    public function calculateFinalAttendance(): bool
    {
        if ($this->is_calculated) {
            return true; // Already calculated
        }

        $session = $this->session;
        if (! $session) {
            return false;
        }

        // Get grace period from academy settings
        $academy = $this->getAcademyForSession($session);
        $graceMinutes = $academy?->settings?->default_late_tolerance_minutes ?? 15;
        $sessionDuration = $session->duration_minutes ?? 60;
        $sessionStartTime = $session->scheduled_at;

        // Calculate attendance status using centralized trait
        $attendanceStatus = $this->determineAttendanceStatusForFinal(
            $sessionStartTime,
            $sessionDuration,
            $graceMinutes
        );

        // Calculate attendance percentage
        $attendancePercentage = $sessionDuration > 0
            ? ($this->total_duration_minutes / $sessionDuration) * 100
            : 0;

        $this->update([
            'attendance_status' => $attendanceStatus,
            'attendance_percentage' => min(100, $attendancePercentage),
            'session_duration_minutes' => $sessionDuration,
            'session_start_time' => $sessionStartTime,
            'session_end_time' => $sessionStartTime->copy()->addMinutes($sessionDuration),
            'attendance_calculated_at' => AcademyContextService::nowInAcademyTimezone(),
            'is_calculated' => true,
        ]);

        Log::info('Final attendance calculated', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'attendance_status' => $attendanceStatus,
            'attendance_percentage' => $attendancePercentage,
            'total_duration' => $this->total_duration_minutes,
            'session_duration' => $sessionDuration,
        ]);

        return true;
    }

    /**
     * Determine attendance status based on join time and duration.
     * Uses centralized AttendanceCalculatorTrait for calculation logic.
     */
    private function determineAttendanceStatusForFinal(
        Carbon $sessionStartTime,
        int $sessionDuration,
        int $graceMinutes
    ): string {
        // Delegate to centralized trait method
        return $this->calculateAttendanceStatus(
            $this->first_join_time,
            $sessionStartTime,
            $sessionDuration,
            $this->total_duration_minutes,
            $graceMinutes
        );
    }

    /**
     * Get academy for a session based on its type
     * Different session types have different paths to academy
     */
    private function getAcademyForSession($session): ?Academy
    {
        // For InteractiveCourseSession, academy is accessed via course
        if ($session instanceof \App\Models\InteractiveCourseSession) {
            return $session->course?->academy;
        }

        // For QuranSession and AcademicSession, academy is direct
        return $session->academy;
    }

    /**
     * Check if user is currently in the meeting based on database cycles
     * SOURCE OF TRUTH: Database cycles (created by manual join API or webhooks)
     * This is FAST and doesn't hit external APIs on every check
     */
    public function isCurrentlyInMeeting(): bool
    {
        $cycles = $this->join_leave_cycles ?? [];

        if (empty($cycles)) {
            return false;
        }

        // Check for open cycle (joined but not left)
        foreach (array_reverse($cycles) as $cycle) {
            if (isset($cycle['joined_at']) && ! isset($cycle['left_at'])) {
                // Found open cycle - but check if it's stale (> 5 minutes with no activity)
                $joinedAt = \Carbon\Carbon::parse($cycle['joined_at']);
                $minutesAgo = $joinedAt->diffInMinutes(AcademyContextService::nowInAcademyTimezone());

                // If cycle is older than 5 minutes and session has ended, consider it stale
                if ($minutesAgo > 5) {
                    $session = $this->session;
                    if ($session) {
                        $sessionEnd = $session->scheduled_at
                            ? $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 60)
                            : null;

                        // If session has ended, this cycle is stale
                        if ($sessionEnd && AcademyContextService::nowInAcademyTimezone()->isAfter($sessionEnd)) {
                            return false;
                        }
                    }
                }

                return true; // Open cycle found and not stale
            }
        }

        return false; // No open cycles
    }

    /**
     * Auto-close cycle after verifying user is not in LiveKit
     */
    private function autoCloseWithLiveKitVerification(): void
    {
        $cycles = $this->join_leave_cycles ?? [];
        $lastCycleIndex = count($cycles) - 1;

        if ($lastCycleIndex >= 0 && ! isset($cycles[$lastCycleIndex]['left_at'])) {
            $now = AcademyContextService::nowInAcademyTimezone();
            $joinTime = Carbon::parse($cycles[$lastCycleIndex]['joined_at']);

            // Close the cycle
            $cycles[$lastCycleIndex]['left_at'] = $now->toISOString();

            // Calculate duration (with session boundary capping)
            $session = $this->session;
            $effectiveJoinTime = $joinTime;

            if ($session && $session->scheduled_at && $joinTime->lessThan($session->scheduled_at)) {
                $effectiveJoinTime = $session->scheduled_at;
            }

            $duration = $effectiveJoinTime->diffInMinutes($now);
            $cycles[$lastCycleIndex]['duration_minutes'] = $duration;

            // Update the attendance record
            $this->update([
                'join_leave_cycles' => $cycles,
                'leave_count' => $this->leave_count + 1,
                'last_leave_time' => $now,
                'total_duration_minutes' => $this->calculateTotalDuration($cycles),
            ]);

            Log::info('Auto-closed stale cycle after LiveKit verification', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
                'duration' => $duration,
                'reason' => 'User not found in LiveKit room',
            ]);
        }
    }

    /**
     * Auto-close stale cycles for sessions that have ended
     * 🔥 IMPROVED: Less strict conditions to ensure cycles are closed properly
     */
    private function autoCloseStaleCycles(): void
    {
        $cycles = $this->join_leave_cycles ?? [];
        $hasChanges = false;

        // Get session to validate timing
        $session = $this->session;
        if (! $session) {
            Log::warning('Cannot validate stale cycles - session not found', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
            ]);

            return; // Can't validate without session context
        }

        // Calculate when session actually ended (scheduled + duration + grace period)
        $sessionDuration = $session->duration_minutes ?? 60;
        $graceMinutes = self::POST_SESSION_GRACE_MINUTES;
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $sessionStart->copy()->addMinutes($sessionDuration)->addMinutes($graceMinutes);

        $now = AcademyContextService::nowInAcademyTimezone();

        foreach ($cycles as $index => $cycle) {
            // 🔥 NEW: Support both webhook format and manual format
            $isWebhookFormat = isset($cycle['type']) && $cycle['type'] === 'join';
            $isManualFormat = isset($cycle['joined_at']) && ! isset($cycle['left_at']);

            if (! $isWebhookFormat && ! $isManualFormat) {
                continue; // Skip if not an open cycle
            }

            // Extract join time based on format
            $joinTime = null;
            if ($isWebhookFormat) {
                $joinTime = is_string($cycle['timestamp']) ? Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];
            } elseif ($isManualFormat) {
                $joinTime = Carbon::parse($cycle['joined_at']);
            }

            if (! $joinTime) {
                continue;
            }

            // 🔥 IMPROVED: Simpler logic - close if session has ended
            $sessionHasEnded = $now->greaterThan($sessionEnd);

            if ($sessionHasEnded) {
                // Session has ended - close the cycle at session end time (before grace period)
                $actualSessionEnd = $sessionStart->copy()->addMinutes($sessionDuration);
                $estimatedLeaveTime = $actualSessionEnd; // Close at actual session end, not grace period end

                // Cap effective join time at session start (don't count preparation time)
                $effectiveJoinTime = $joinTime;
                if ($joinTime->lessThan($sessionStart)) {
                    $effectiveJoinTime = $sessionStart;
                    Log::debug('Auto-close: Capping cycle at session start', [
                        'session_id' => $this->session_id,
                        'user_id' => $this->user_id,
                        'actual_join' => $joinTime->toISOString(),
                        'effective_join' => $effectiveJoinTime->toISOString(),
                        'session_start' => $sessionStart->toISOString(),
                    ]);
                }

                // Calculate actual duration from effective join to estimated leave
                $actualDuration = $effectiveJoinTime->diffInMinutes($estimatedLeaveTime);

                // Update cycle based on format
                if ($isManualFormat) {
                    $cycles[$index]['left_at'] = $estimatedLeaveTime->toISOString();
                    $cycles[$index]['duration_minutes'] = $actualDuration;
                    $cycles[$index]['auto_closed'] = true;
                    $cycles[$index]['auto_close_reason'] = 'session_ended';
                }
                // Note: We don't auto-close webhook format cycles here - they should get proper leave webhooks

                $hasChanges = true;

                Log::info('Auto-closed stale attendance cycle', [
                    'session_id' => $this->session_id,
                    'user_id' => $this->user_id,
                    'cycle_format' => $isWebhookFormat ? 'webhook' : 'manual',
                    'actual_join_time' => $joinTime->toISOString(),
                    'effective_join_time' => $effectiveJoinTime->toISOString(),
                    'estimated_leave_time' => $estimatedLeaveTime->toISOString(),
                    'actual_duration' => $actualDuration,
                    'session_start' => $sessionStart->toISOString(),
                    'session_end' => $actualSessionEnd->toISOString(),
                    'session_duration' => $sessionDuration,
                ]);
            } else {
                Log::debug('Cycle not auto-closed - session still within grace period', [
                    'session_id' => $this->session_id,
                    'user_id' => $this->user_id,
                    'session_end_with_grace' => $sessionEnd->toISOString(),
                    'now' => $now->toISOString(),
                    'minutes_until_end' => $now->diffInMinutes($sessionEnd, false),
                ]);
            }
        }

        if ($hasChanges) {
            $this->update([
                'join_leave_cycles' => $cycles,
                'leave_count' => $this->leave_count + 1,
                'total_duration_minutes' => $this->calculateTotalDuration($cycles),
                'last_leave_time' => $this->extractLastLeaveTime($cycles),
            ]);

            Log::info('Stale cycles auto-closed and attendance updated', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
                'new_total_duration' => $this->fresh()->total_duration_minutes,
            ]);
        }
    }

    /**
     * Extract last leave time from cycles (supports both formats)
     */
    private function extractLastLeaveTime(array $cycles): ?Carbon
    {
        for ($i = count($cycles) - 1; $i >= 0; $i--) {
            $cycle = $cycles[$i];

            // Manual format
            if (isset($cycle['left_at'])) {
                return Carbon::parse($cycle['left_at']);
            }

            // Webhook format
            if (isset($cycle['type']) && $cycle['type'] === 'leave' && isset($cycle['timestamp'])) {
                return is_string($cycle['timestamp']) ? Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];
            }
        }

        return null;
    }

    /**
     * Get the current session duration if user is still in meeting
     * IMPROVED: Cap duration at session end time AND session start time to avoid inflated attendance
     * CRITICAL: Only calculate during actual session time, not before or after
     */
    public function getCurrentSessionDuration(): int
    {
        if (! $this->isCurrentlyInMeeting()) {
            return $this->total_duration_minutes;
        }

        $cycles = $this->join_leave_cycles ?? [];
        $lastCycle = end($cycles);

        if (! $lastCycle || ! isset($lastCycle['joined_at'])) {
            return $this->total_duration_minutes;
        }

        $joinTime = Carbon::parse($lastCycle['joined_at']);
        $now = AcademyContextService::nowInAcademyTimezone();
        $session = $this->session;

        // CRITICAL FIX: Only calculate during actual session time
        if ($session && $session->scheduled_at) {
            $sessionStart = $session->scheduled_at;
            $sessionDuration = $session->duration_minutes ?? 60;
            $graceMinutes = self::POST_SESSION_GRACE_MINUTES;
            $sessionEnd = $sessionStart->copy()
                ->addMinutes($sessionDuration)
                ->addMinutes($graceMinutes);

            // BEFORE session starts: Don't calculate, return completed duration only
            if ($now->lessThan($sessionStart)) {
                Log::debug('Session not started yet - not calculating current cycle', [
                    'session_id' => $this->session_id,
                    'user_id' => $this->user_id,
                    'session_start' => $sessionStart->toISOString(),
                    'now' => $now->toISOString(),
                    'minutes_until_start' => $now->diffInMinutes($sessionStart, false),
                ]);

                return $this->total_duration_minutes; // Don't count current cycle before session starts
            }

            // AFTER session ends: Auto-close cycle and return completed duration
            if ($now->greaterThan($sessionEnd)) {
                Log::info('Session has ended - auto-closing open cycle and stopping calculation', [
                    'session_id' => $this->session_id,
                    'user_id' => $this->user_id,
                    'session_end' => $sessionEnd->toISOString(),
                    'now' => $now->toISOString(),
                ]);

                // Trigger auto-close for stale cycles
                $this->autoCloseStaleCycles();

                // Return the completed duration (after auto-close)
                return $this->fresh()->total_duration_minutes;
            }
        }

        // DURING session: Calculate real-time duration
        // Cap effective join time at session start (don't count preparation time)
        $effectiveJoinTime = $joinTime;
        if ($session && $session->scheduled_at) {
            $sessionStart = $session->scheduled_at;

            // If user joined before session started, use session start as effective join time
            if ($joinTime->lessThan($sessionStart)) {
                $effectiveJoinTime = $sessionStart;

                Log::debug('Capped current cycle join time at session start', [
                    'session_id' => $this->session_id,
                    'user_id' => $this->user_id,
                    'actual_join' => $joinTime->toISOString(),
                    'effective_join' => $effectiveJoinTime->toISOString(),
                    'session_start' => $sessionStart->toISOString(),
                ]);
            }
        }

        // Calculate duration for current open cycle from effective join time
        $currentCycleDuration = $effectiveJoinTime->diffInMinutes($now);

        $totalDuration = $this->total_duration_minutes + $currentCycleDuration;

        Log::debug('Calculated current session duration (session is running)', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'completed_duration' => $this->total_duration_minutes,
            'current_cycle_duration' => $currentCycleDuration,
            'total_duration' => $totalDuration,
            'effective_join_time' => $effectiveJoinTime->toISOString(),
        ]);

        return $totalDuration;
    }

    /**
     * Update heartbeat timestamp
     */
    public function updateHeartbeat(): void
    {
        $now = AcademyContextService::nowInAcademyTimezone();
        $this->update(['last_heartbeat_at' => $now]);

        Log::debug('Heartbeat updated', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'last_heartbeat_at' => $now->toISOString(),
        ]);
    }

    /**
     * Check if heartbeat is stale (no heartbeat for 5+ minutes)
     */
    public function hasStaleHeartbeat(): bool
    {
        if (! $this->last_heartbeat_at) {
            return false; // No heartbeat data yet
        }

        $minutesSinceHeartbeat = $this->last_heartbeat_at->diffInMinutes(AcademyContextService::nowInAcademyTimezone());

        return $minutesSinceHeartbeat > 5;
    }

    /**
     * Scopes
     */
    public function scopePresent($query)
    {
        return $query->where('attendance_status', AttendanceStatus::ATTENDED->value);
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', AttendanceStatus::ABSENT->value);
    }

    public function scopeLate($query)
    {
        return $query->where('attendance_status', AttendanceStatus::LATE->value);
    }

    public function scopePartial($query)
    {
        return $query->where('attendance_status', AttendanceStatus::LEFT->value);
    }

    public function scopeCalculated($query)
    {
        return $query->where('is_calculated', true);
    }

    public function scopeNotCalculated($query)
    {
        return $query->where('is_calculated', false);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Static method to find or create meeting attendance for any session type
     */
    public static function findOrCreateForUser(MeetingCapable $session, User $user): self
    {
        return static::firstOrCreate([
            'session_id' => $session->id,
            'user_id' => $user->id,
        ], [
            'user_type' => $user->user_type === 'quran_teacher' ? 'teacher' : 'student',
            'session_type' => $session->session_type ?? 'individual',
            'join_leave_cycles' => [],
            'join_count' => 0,
            'leave_count' => 0,
            'total_duration_minutes' => 0,
            'attendance_status' => AttendanceStatus::ABSENT->value,
            'attendance_percentage' => 0,
            'is_calculated' => false,
        ]);
    }

    /**
     * Static method to find or create meeting attendance (Polymorphic version)
     */
    public static function findOrCreateForUserPolymorphic($session, User $user, string $sessionType): self
    {
        $userType = 'student'; // Default

        if ($sessionType === 'academic') {
            $userType = $user->user_type === 'academic_teacher' ? 'teacher' : 'student';
        } else {
            $userType = $user->user_type === 'quran_teacher' ? 'teacher' : 'student';
        }

        // Map the polymorphic session type to the database enum values
        // $sessionType comes in as 'quran' or 'academic' from the request
        // But the database expects 'individual', 'group', or 'academic'
        $dbSessionType = match ($sessionType) {
            'academic' => 'academic',
            'quran' => $session->session_type ?? 'group', // Use the actual session type from the session model
            default => $session->session_type ?? 'group',
        };

        return static::firstOrCreate([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'session_type' => $dbSessionType,
        ], [
            'user_type' => $userType,
            'join_leave_cycles' => [],
            'join_count' => 0,
            'leave_count' => 0,
            'total_duration_minutes' => 0,
            'attendance_status' => AttendanceStatus::ABSENT->value,
            'attendance_percentage' => 0,
            'is_calculated' => false,
        ]);
    }
}
