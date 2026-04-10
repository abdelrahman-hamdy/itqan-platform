<?php

namespace App\Jobs;

use App\Enums\AttendanceStatus;
use App\Events\AttendanceUpdated;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\MeetingAttendance;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Services\Traits\AttendanceCalculatorTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Per-Session Attendance Calculation Job
 *
 * Dispatched from the LiveKit webhook when a specific room finishes.
 * Calculates attendance for ONE session only, enabling parallel processing
 * across many concurrent meetings.
 *
 * ShouldBeUnique scoped per session (class + ID) to prevent duplicate calculation.
 */
class CalculateSessionForAttendance implements ShouldBeUnique, ShouldQueue
{
    use AttendanceCalculatorTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allowed session classes (security allowlist).
     */
    private const ALLOWED_SESSION_CLASSES = [
        QuranSession::class,
        AcademicSession::class,
        InteractiveCourseSession::class,
    ];

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying with exponential backoff.
     */
    public array $backoff = [30, 60, 120];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds the unique lock should be held.
     */
    public int $uniqueFor = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $sessionId,
        public readonly string $sessionClass
    ) {
        $this->onQueue('attendance');
    }

    /**
     * The unique ID of the job (scoped per session type + ID).
     */
    public function uniqueId(): string
    {
        return "session-{$this->sessionClass}-{$this->sessionId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Validate session class against allowlist
        if (! in_array($this->sessionClass, self::ALLOWED_SESSION_CLASSES, true)) {
            Log::error('CalculateSessionForAttendance: Invalid session class', [
                'session_class' => $this->sessionClass,
                'session_id' => $this->sessionId,
            ]);

            return;
        }

        $session = ($this->sessionClass)::find($this->sessionId);

        if (! $session) {
            Log::warning('CalculateSessionForAttendance: Session not found', [
                'session_class' => $this->sessionClass,
                'session_id' => $this->sessionId,
            ]);

            return;
        }

        // Check grace period: if session hasn't ended long enough ago, release back to queue
        $calculationDelayMinutes = config('business.attendance.calculation_delay_minutes', 5);
        $sessionEnd = $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 60);
        $gracePeriodEnd = $sessionEnd->copy()->addMinutes($calculationDelayMinutes);

        if (now()->lt($gracePeriodEnd)) {
            $releaseSeconds = max(30, (int) now()->diffInSeconds($gracePeriodEnd));
            Log::info('CalculateSessionForAttendance: Session still within grace period, releasing', [
                'session_id' => $this->sessionId,
                'grace_period_ends' => $gracePeriodEnd->toISOString(),
                'release_seconds' => $releaseSeconds,
            ]);
            $this->release($releaseSeconds);

            return;
        }

        // Load uncalculated attendance records with lock to prevent races
        $attendances = DB::transaction(function () {
            return MeetingAttendance::where('session_id', $this->sessionId)
                ->where('is_calculated', false)
                ->lockForUpdate()
                ->get();
        });

        if ($attendances->isEmpty()) {
            Log::info('CalculateSessionForAttendance: No uncalculated records', [
                'session_id' => $this->sessionId,
            ]);

            // Create absent MeetingAttendance for student if none exists (nobody joined)
            if ($session->student_id) {
                MeetingAttendance::firstOrCreate(
                    ['session_id' => $session->id, 'user_id' => $session->student_id, 'user_type' => 'student'],
                    [
                        'session_type' => method_exists($session, 'getAttendanceSessionType') ? $session->getAttendanceSessionType() : 'individual',
                        'attendance_status' => AttendanceStatus::ABSENT,
                        'total_duration_minutes' => 0,
                        'attendance_percentage' => 0,
                        'is_calculated' => true,
                        'session_duration_minutes' => $session->duration_minutes,
                    ]
                );
            }

            // Calculate teacher attendance and set counting flags
            $this->calculateTeacherAttendanceAndSetFlags($session);

            return;
        }

        $processed = 0;
        $failed = 0;

        foreach ($attendances as $attendance) {
            try {
                // Double-check with lock inside transaction to prevent race with global job
                DB::transaction(function () use ($session, $attendance, &$processed) {
                    $freshAttendance = MeetingAttendance::where('id', $attendance->id)
                        ->where('is_calculated', false)
                        ->lockForUpdate()
                        ->first();

                    if (! $freshAttendance) {
                        // Already calculated by another process
                        return;
                    }

                    $this->calculateAttendance($session, $freshAttendance);
                    $processed++;
                });
            } catch (Exception $e) {
                Log::error('CalculateSessionForAttendance: Failed to calculate attendance', [
                    'session_id' => $this->sessionId,
                    'user_id' => $attendance->user_id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        // Calculate teacher attendance and auto-set counting flags
        $this->calculateTeacherAttendanceAndSetFlags($session);

        Log::info('CalculateSessionForAttendance: Completed', [
            'session_id' => $this->sessionId,
            'session_class' => class_basename($this->sessionClass),
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }

    /**
     * Calculate teacher attendance status and auto-set counting flags.
     *
     * After all student attendance is calculated, find the teacher's
     * MeetingAttendance record, calculate their status using teacher-specific
     * thresholds, then auto-set counts_for_teacher and counts_for_subscription.
     */
    private function calculateTeacherAttendanceAndSetFlags($session): void
    {
        try {
            $teacherAttendance = MeetingAttendance::where('session_id', $session->id)
                ->whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])
                ->first();

            $sessionDuration = $session->duration_minutes ?? 60;

            // Get teacher-specific thresholds from academy settings
            $settingsService = app(\App\Services\SessionSettingsService::class);
            $fullPercent = $settingsService->getTeacherFullAttendancePercent($session);
            $partialPercent = $settingsService->getTeacherPartialAttendancePercent($session);

            // Calculate teacher attendance status
            $teacherStatus = AttendanceStatus::ABSENT;
            if ($teacherAttendance && $teacherAttendance->first_join_time) {
                $totalMinutes = $teacherAttendance->total_duration_minutes ?? 0;
                $statusValue = $this->calculateTeacherAttendanceStatus(
                    $teacherAttendance->first_join_time,
                    $sessionDuration,
                    $totalMinutes,
                    $fullPercent,
                    $partialPercent,
                );
                $teacherStatus = AttendanceStatus::from($statusValue);
            }

            // Store teacher attendance on session
            $updateData = [
                'teacher_attendance_status' => $teacherStatus->value,
                'teacher_attendance_calculated_at' => now(),
            ];

            // Auto-set counts_for_teacher only if not already overridden by admin
            if ($session->counts_for_teacher_set_by === null) {
                $teacherCounts = $teacherStatus !== AttendanceStatus::ABSENT;
                $updateData['counts_for_teacher'] = $teacherCounts;

                // If teacher was absent, auto-exclude all students too (not their fault)
                if (! $teacherCounts) {
                    MeetingAttendance::where('session_id', $session->id)
                        ->where('user_type', 'student')
                        ->whereNull('counts_for_subscription_set_by')
                        ->update(['counts_for_subscription' => false]);

                    Log::info('CalculateSessionForAttendance: Teacher absent, auto-excluded all students', [
                        'session_id' => $session->id,
                    ]);
                }
            }

            $session->update($updateData);

            Log::info('CalculateSessionForAttendance: Teacher attendance calculated', [
                'session_id' => $session->id,
                'teacher_status' => $teacherStatus->value,
                'counts_for_teacher' => $updateData['counts_for_teacher'] ?? 'admin_override',
            ]);

            // Refresh to pick up the counts_for_teacher value we just persisted.
            $session->refresh();

            // Count subscription usage when teacher was NOT explicitly absent.
            // counts_for_teacher can be true (present) or null (not yet determined,
            // admin didn't override, auto-calc hadn't run). Only `false` should skip counting.
            if ($session->counts_for_teacher !== false && method_exists($session, 'updateSubscriptionUsage')) {
                try {
                    $session->updateSubscriptionUsage();

                    // Belt-and-suspenders: ensure student attendance flag is set for UI display.
                    // updateSubscriptionUsage() already syncs this for its own session, but keep
                    // this as a safety net for any student attendance rows it may have missed.
                    MeetingAttendance::where('session_id', $session->id)
                        ->where('user_type', 'student')
                        ->whereNull('counts_for_subscription_set_by')
                        ->whereNull('counts_for_subscription')
                        ->update(['counts_for_subscription' => true]);
                } catch (Exception $e) {
                    Log::error('CalculateSessionForAttendance: Failed to update subscription usage', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Dispatch earnings calculation NOW that counts_for_teacher is set.
            // This ensures the earnings job sees the correct attendance data.
            dispatch(new CalculateSessionEarningsJob($session));
        } catch (Exception $e) {
            Log::error('CalculateSessionForAttendance: Failed to calculate teacher attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate final attendance for a single attendance record.
     */
    private function calculateAttendance($session, MeetingAttendance $attendance): void
    {
        $cycles = $attendance->join_leave_cycles ?? [];

        // Session timing info
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $session->scheduled_end_at ?? $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60);

        // Reconcile any open cycles before calculating
        $cycles = $this->reconcileOpenCycles($cycles, $session, $attendance, $sessionEnd);
        $sessionDuration = $sessionStart->diffInMinutes($sessionEnd);

        // Calculate total duration from cycles, excluding preparation and buffer time
        $totalMinutes = $this->calculateTotalDuration($cycles, $sessionStart, $sessionEnd);

        // Tolerance time (grace period for late arrival)
        $toleranceMinutes = config('business.attendance.grace_period_minutes', 15);

        // Determine first join time
        $firstJoinTime = $attendance->first_join_time;

        // Calculate attendance percentage (capped at session start time)
        $attendancePercentage = $sessionDuration > 0 ? min(100, ($totalMinutes / $sessionDuration) * 100) : 0;

        // Determine attendance status using centralized trait logic
        $status = $this->determineAttendanceStatusFromTrait(
            $firstJoinTime,
            $sessionStart,
            $sessionDuration,
            $totalMinutes,
            $toleranceMinutes
        );

        // Update attendance record
        $attendance->update([
            'total_duration_minutes' => $totalMinutes,
            'session_duration_minutes' => $sessionDuration,
            'attendance_status' => $status->value,
            'attendance_percentage' => round($attendancePercentage, 2),
            'is_calculated' => true,
            'attendance_calculated_at' => now(),
        ]);

        // Broadcast attendance update for instant UI refresh
        $this->broadcastAttendanceUpdated($session, $attendance, $status, $attendancePercentage, $totalMinutes, $sessionDuration);

        // Sync to session report
        $this->syncToReport($session, $attendance);

        Log::info('CalculateSessionForAttendance: Attendance calculated', [
            'session_id' => $session->id,
            'user_id' => $attendance->user_id,
            'status' => $status->value,
            'duration' => $totalMinutes,
            'percentage' => round($attendancePercentage, 2),
        ]);
    }

    /**
     * Broadcast attendance updated event for instant UI refresh.
     */
    private function broadcastAttendanceUpdated(
        $session,
        MeetingAttendance $attendance,
        AttendanceStatus $status,
        float $attendancePercentage,
        int $totalMinutes,
        int $sessionDuration
    ): void {
        try {
            event(new AttendanceUpdated(
                $session->id,
                $attendance->user_id,
                [
                    'attendance_status' => $status->value,
                    'attendance_percentage' => round($attendancePercentage, 2),
                    'total_duration_minutes' => $totalMinutes,
                    'session_duration_minutes' => $sessionDuration,
                    'first_join_time' => $attendance->first_join_time?->toISOString(),
                    'last_leave_time' => $attendance->last_leave_time?->toISOString(),
                    'is_calculated' => true,
                ]
            ));
        } catch (Exception $e) {
            // Don't fail the job if broadcast fails — attendance is already saved
            Log::warning('CalculateSessionForAttendance: Failed to broadcast attendance update', [
                'session_id' => $session->id,
                'user_id' => $attendance->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate total duration from join/leave cycles.
     *
     * Only count time within session's original start/end times:
     * - Preparation time (before session start) is excluded
     * - Buffer time (after session end) is excluded
     */
    private function calculateTotalDuration(array $cycles, Carbon $sessionStart, Carbon $sessionEnd): int
    {
        $totalMinutes = 0;
        $lastJoinTime = null;

        foreach ($cycles as $cycle) {
            $isWebhookFormat = isset($cycle['type']);
            $isManualFormat = isset($cycle['joined_at']);

            if ($isWebhookFormat) {
                if ($cycle['type'] === 'join') {
                    $lastJoinTime = $cycle['timestamp'];
                } elseif ($cycle['type'] === 'leave' && $lastJoinTime) {
                    $joinTime = is_string($lastJoinTime) ? Carbon::parse($lastJoinTime) : $lastJoinTime;
                    $leaveTime = is_string($cycle['timestamp']) ? Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];

                    if ($joinTime->lt($sessionStart)) {
                        $joinTime = $sessionStart->copy();
                    }
                    if ($leaveTime->gt($sessionEnd)) {
                        $leaveTime = $sessionEnd->copy();
                    }
                    if ($joinTime->lt($leaveTime)) {
                        $totalMinutes += (int) round($joinTime->diffInMinutes($leaveTime));
                    }

                    $lastJoinTime = null;
                }
            } elseif ($isManualFormat) {
                if (isset($cycle['joined_at']) && isset($cycle['left_at'])) {
                    $joinTime = is_string($cycle['joined_at']) ? Carbon::parse($cycle['joined_at']) : $cycle['joined_at'];
                    $leaveTime = is_string($cycle['left_at']) ? Carbon::parse($cycle['left_at']) : $cycle['left_at'];

                    if ($joinTime->lt($sessionStart)) {
                        $joinTime = $sessionStart->copy();
                    }
                    if ($leaveTime->gt($sessionEnd)) {
                        $leaveTime = $sessionEnd->copy();
                    }
                    if ($joinTime->lt($leaveTime)) {
                        $totalMinutes += (int) round($joinTime->diffInMinutes($leaveTime));
                    }
                }
            }
        }

        return $totalMinutes;
    }

    /**
     * Reconcile open join cycles that have no matching leave.
     */
    private function reconcileOpenCycles(array $cycles, $session, MeetingAttendance $attendance, Carbon $sessionEnd): array
    {
        $hasChanges = false;
        $fallbackLeaveTime = $session->ended_at ?? $sessionEnd;

        $i = 0;
        while ($i < count($cycles)) {
            $cycle = $cycles[$i];

            $isOpenWebhookJoin = isset($cycle['type']) && $cycle['type'] === 'join'
                && ! $this->hasMatchingLeave($cycles, $i);
            $isOpenManualCycle = isset($cycle['joined_at']) && ! isset($cycle['left_at']);

            if (! $isOpenWebhookJoin && ! $isOpenManualCycle) {
                $i++;

                continue;
            }

            $participantSid = $cycle['participant_sid'] ?? null;
            $closedEvent = null;

            if ($participantSid) {
                $closedEvent = MeetingAttendanceEvent::where('session_id', $session->id)
                    ->where('participant_sid', $participantSid)
                    ->whereNotNull('left_at')
                    ->first();
            }

            $leaveTime = $closedEvent?->left_at ?? $fallbackLeaveTime;

            if ($isOpenWebhookJoin) {
                array_splice($cycles, $i + 1, 0, [[
                    'type' => 'leave',
                    'timestamp' => $leaveTime instanceof Carbon ? $leaveTime->toISOString() : (string) $leaveTime,
                    'auto_reconciled' => true,
                    'reconciled_from' => $closedEvent ? 'event_table' : 'session_end',
                ]]);
                $i += 2;
            } elseif ($isOpenManualCycle) {
                $cycles[$i]['left_at'] = $leaveTime instanceof Carbon ? $leaveTime->toISOString() : (string) $leaveTime;
                $cycles[$i]['auto_reconciled'] = true;
                $cycles[$i]['reconciled_from'] = $closedEvent ? 'event_table' : 'session_end';
                $i++;
            }

            $hasChanges = true;
        }

        if ($hasChanges) {
            $attendance->update(['join_leave_cycles' => $cycles]);
        }

        return $cycles;
    }

    /**
     * Check if a webhook join event at the given index has a matching leave event.
     */
    private function hasMatchingLeave(array $cycles, int $joinIndex): bool
    {
        $joinCycle = $cycles[$joinIndex];
        $participantSid = $joinCycle['participant_sid'] ?? null;

        for ($i = $joinIndex + 1; $i < count($cycles); $i++) {
            $cycle = $cycles[$i];
            if (isset($cycle['type']) && $cycle['type'] === 'leave') {
                if ($participantSid && isset($cycle['participant_sid']) && $cycle['participant_sid'] === $participantSid) {
                    return true;
                }
                if (! $participantSid) {
                    return true;
                }
            }
            if (isset($cycle['type']) && $cycle['type'] === 'join') {
                break;
            }
        }

        return false;
    }

    /**
     * Determine attendance status using centralized trait logic.
     */
    private function determineAttendanceStatusFromTrait(
        ?Carbon $firstJoinTime,
        Carbon $sessionStartTime,
        int $sessionDurationMinutes,
        int $totalAttendanceMinutes,
        int $toleranceMinutes
    ): AttendanceStatus {
        return $this->calculateAttendanceStatusEnum(
            $firstJoinTime,
            $sessionStartTime,
            $sessionDurationMinutes,
            $totalAttendanceMinutes,
            $toleranceMinutes
        );
    }

    /**
     * Sync calculated attendance to session report.
     */
    private function syncToReport($session, MeetingAttendance $attendance): void
    {
        try {
            // Skip teacher participants
            if (in_array($attendance->user_type, ['teacher', 'quran_teacher', 'academic_teacher'])) {
                return;
            }

            $reportClass = $this->getReportClass($session);
            if (! $reportClass) {
                return;
            }

            $report = $reportClass::firstOrNew([
                'session_id' => $session->id,
                'student_id' => $attendance->user_id,
            ]);

            $teacherId = $session->teacher_id ?? $session->quran_teacher_id ?? null;
            $academyId = $session->academy_id
                ?? $session->course?->academy_id
                ?? $session->quran_circle?->academy_id;

            if (! $academyId) {
                Log::error('CalculateSessionForAttendance: Cannot determine academy_id for report', [
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                ]);

                return;
            }

            $report->fill([
                'teacher_id' => $teacherId,
                'academy_id' => $academyId,
                'meeting_enter_time' => $attendance->first_join_time,
                'meeting_leave_time' => $attendance->last_leave_time,
                'actual_attendance_minutes' => $attendance->total_duration_minutes,
                'attendance_status' => $attendance->attendance_status,
                'attendance_percentage' => $attendance->attendance_percentage,
                'is_late' => $attendance->first_join_time && $attendance->first_join_time->gt($session->scheduled_at->copy()->addMinutes(config('business.attendance.late_threshold_minutes', 15))),
                'late_minutes' => $attendance->first_join_time && $attendance->first_join_time->gt($session->scheduled_at)
                    ? (int) $session->scheduled_at->diffInMinutes($attendance->first_join_time)
                    : 0,
                'is_calculated' => true,
                'evaluated_at' => now(),
            ]);

            $report->save();
        } catch (Exception $e) {
            Log::error('CalculateSessionForAttendance: Failed to sync to report', [
                'session_id' => $session->id,
                'user_id' => $attendance->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the appropriate report class for a session.
     */
    private function getReportClass($session): ?string
    {
        if ($session instanceof QuranSession) {
            return StudentSessionReport::class;
        }

        if ($session instanceof AcademicSession) {
            return AcademicSessionReport::class;
        }

        if ($session instanceof InteractiveCourseSession) {
            return InteractiveSessionReport::class;
        }

        return null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('CalculateSessionForAttendance job failed permanently', [
            'session_id' => $this->sessionId,
            'session_class' => $this->sessionClass,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
