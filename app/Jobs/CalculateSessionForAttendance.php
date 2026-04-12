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
     * Set counts_for_teacher and per-student counts_for_subscription based on
     * the attendance counting matrix:
     *
     *   teacher ≥ teacher_full%     → earnings YES, every student counts
     *   teacher ≥ teacher_partial%  → earnings NO,  every student counts
     *   teacher <  teacher_partial% → earnings NO; student present refunded,
     *                                 student absent counts (no-show penalty)
     */
    private function calculateTeacherAttendanceAndSetFlags($session): void
    {
        try {
            $matrixEnabled = (bool) config('business.attendance.use_matrix_counting', true);

            $teacherAttendance = MeetingAttendance::where('session_id', $session->id)
                ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
                ->first();

            $sessionDuration = $session->duration_minutes ?? 60;

            // Teacher thresholds
            $settingsService = app(\App\Services\SessionSettingsService::class);
            $teacherFullPercent = $settingsService->getTeacherFullAttendancePercent($session);
            $teacherPartialPercent = $settingsService->getTeacherPartialAttendancePercent($session);
            $studentPartialPercent = $settingsService->getStudentPartialAttendancePercent($session);

            // Compute teacher attendance percentage and display status
            $teacherPct = 0.0;
            $teacherStatus = AttendanceStatus::ABSENT;

            if ($teacherAttendance && $teacherAttendance->first_join_time && $sessionDuration > 0) {
                $totalMinutes = $teacherAttendance->total_duration_minutes ?? 0;
                $teacherPct = ($totalMinutes / $sessionDuration) * 100;
                $statusValue = $this->calculateTeacherAttendanceStatus(
                    $teacherAttendance->first_join_time,
                    $sessionDuration,
                    $totalMinutes,
                    $teacherFullPercent,
                    $teacherPartialPercent,
                );
                $teacherStatus = AttendanceStatus::from($statusValue);
            }

            $teacherPresent = $teacherPct >= $teacherPartialPercent;
            $teacherEarns = $teacherPct >= $teacherFullPercent;

            $updateData = [
                'teacher_attendance_status' => $teacherStatus->value,
                'teacher_attendance_calculated_at' => now(),
            ];
            // Respect admin overrides — only auto-set if nobody manually set it.
            if ($session->counts_for_teacher_set_by === null) {
                $updateData['counts_for_teacher'] = $teacherEarns;
            }

            $session->update($updateData);

            // Per-student matrix pass. `whereNull('counts_for_subscription')`
            // is the historical-safety guard: we never overwrite a value that
            // already exists, so pre-refactor rows and admin overrides are
            // both preserved.
            if ($matrixEnabled) {
                $baseQuery = $session->meetingAttendances()
                    ->where('user_type', 'student')
                    ->whereNull('counts_for_subscription_set_by')
                    ->whereNull('counts_for_subscription');

                if ($teacherPresent) {
                    // Rule 2 + 3 + row 4 + row 5: teacher present → every student counts.
                    (clone $baseQuery)->update(['counts_for_subscription' => true]);
                } else {
                    // Teacher absent — split by student's own percentage.
                    // Student absent → counts (Rule 3, no-show penalty).
                    // Student present → does NOT count (Rule 4, student refunded).
                    (clone $baseQuery)
                        ->where(function ($q) use ($studentPartialPercent) {
                            $q->whereNull('attendance_percentage')
                                ->orWhere('attendance_percentage', '<', $studentPartialPercent);
                        })
                        ->update(['counts_for_subscription' => true]);

                    (clone $baseQuery)
                        ->where('attendance_percentage', '>=', $studentPartialPercent)
                        ->update(['counts_for_subscription' => false]);
                }
            }

            Log::info('CalculateSessionForAttendance: Teacher attendance and matrix applied', [
                'session_id' => $session->id,
                'teacher_status' => $teacherStatus->value,
                'teacher_pct' => round($teacherPct, 2),
                'teacher_present' => $teacherPresent,
                'teacher_earns' => $teacherEarns,
                'counts_for_teacher' => $updateData['counts_for_teacher'] ?? 'admin_override',
                'matrix_enabled' => $matrixEnabled,
            ]);

            // Always call updateSubscriptionUsage — it consults the per-student
            // counts_for_subscription flag internally, so the "both absent"
            // case (counts_for_teacher=false but student still pays) works.
            if (method_exists($session, 'updateSubscriptionUsage')) {
                try {
                    $session->updateSubscriptionUsage();
                } catch (Exception $e) {
                    Log::error('CalculateSessionForAttendance: Failed to update subscription usage', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Dispatch earnings now that counts_for_teacher is set.
            dispatch(new CalculateSessionEarningsJob($session));
        } catch (Exception $e) {
            Log::error('CalculateSessionForAttendance: Failed to calculate teacher attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate final attendance for a single attendance record, populating
     * status (percentage-based), total duration (capped to session window),
     * and display duration (uncapped meeting-window time for UI).
     */
    private function calculateAttendance($session, MeetingAttendance $attendance): void
    {
        $cycles = $attendance->join_leave_cycles ?? [];

        $sessionStart = $session->scheduled_at;
        $sessionEnd = $session->scheduled_end_at ?? $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60);

        $cycles = $this->reconcileOpenCycles($cycles, $session, $attendance, $sessionEnd);
        $sessionDuration = (int) $sessionStart->diffInMinutes($sessionEnd);

        // total = capped to scheduled window (percentage denominator)
        // display = uncapped meeting window (UI only, prep + session + buffer)
        $totalMinutes = $this->calculateTotalDuration($cycles, $sessionStart, $sessionEnd);
        $displayMinutes = $this->calculateDisplayDuration($cycles, $session);

        $settingsService = app(\App\Services\SessionSettingsService::class);
        [$fullPercent, $partialPercent] = $settingsService
            ->getAttendanceThresholdsForUserType($session, $attendance->user_type);

        $attendancePercentage = $sessionDuration > 0 ? min(100, ($totalMinutes / $sessionDuration) * 100) : 0;

        $status = $this->determineAttendanceStatusFromTrait(
            $attendance->first_join_time,
            $sessionDuration,
            $totalMinutes,
            $fullPercent,
            $partialPercent,
        );

        $attendance->update([
            'total_duration_minutes' => $totalMinutes,
            'display_duration_minutes' => $displayMinutes,
            'session_duration_minutes' => $sessionDuration,
            'attendance_status' => $status->value,
            'attendance_percentage' => round($attendancePercentage, 2),
            'is_calculated' => true,
            'attendance_calculated_at' => now(),
        ]);

        // Broadcast attendance update for instant UI refresh
        $this->broadcastAttendanceUpdated($session, $attendance, $status, $attendancePercentage, $totalMinutes, $sessionDuration, $displayMinutes);

        // Sync to session report
        $this->syncToReport($session, $attendance);

        Log::info('CalculateSessionForAttendance: Attendance calculated', [
            'session_id' => $session->id,
            'user_id' => $attendance->user_id,
            'user_type' => $attendance->user_type,
            'status' => $status->value,
            'duration' => $totalMinutes,
            'display_duration' => $displayMinutes,
            'percentage' => round($attendancePercentage, 2),
            'full_percent' => $fullPercent,
            'partial_percent' => $partialPercent,
        ]);
    }

    /**
     * Calculate display duration (uncapped meeting-window time) from cycles.
     * Delegates to AttendanceCalculatorTrait::sumCycleMinutesInWindow().
     */
    private function calculateDisplayDuration(array $cycles, $session): int
    {
        if (! $session || ! $session->scheduled_at) {
            return 0;
        }

        $settingsService = app(\App\Services\SessionSettingsService::class);
        $windowStart = $session->scheduled_at->copy()
            ->subMinutes($settingsService->getPreparationMinutes($session));
        $windowEnd = $session->scheduled_at->copy()
            ->addMinutes(($session->duration_minutes ?? 60) + $settingsService->getBufferMinutes($session));

        return $this->sumCycleMinutesInWindow($cycles, $windowStart, $windowEnd);
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
        int $sessionDuration,
        int $displayMinutes = 0
    ): void {
        try {
            event(new AttendanceUpdated(
                $session->id,
                $attendance->user_id,
                [
                    'attendance_status' => $status->value,
                    'attendance_percentage' => round($attendancePercentage, 2),
                    'total_duration_minutes' => $totalMinutes,
                    'display_duration_minutes' => $displayMinutes,
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
     * Determine attendance status using centralized trait logic (percentage-based).
     */
    private function determineAttendanceStatusFromTrait(
        ?Carbon $firstJoinTime,
        int $sessionDurationMinutes,
        int $totalAttendanceMinutes,
        float $fullPercent,
        float $partialPercent,
    ): AttendanceStatus {
        return $this->calculateAttendanceStatusEnum(
            $firstJoinTime,
            $sessionDurationMinutes,
            $totalAttendanceMinutes,
            $fullPercent,
            $partialPercent,
        );
    }

    /**
     * Sync calculated attendance to session report.
     */
    private function syncToReport($session, MeetingAttendance $attendance): void
    {
        try {
            // Skip teacher participants
            if (in_array($attendance->user_type, MeetingAttendance::TEACHER_USER_TYPES)) {
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
                'is_late' => false,
                'late_minutes' => 0,
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
