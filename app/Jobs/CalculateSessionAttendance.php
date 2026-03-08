<?php

namespace App\Jobs;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Jobs\Traits\TenantAwareJob;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\Academy;
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
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calculate Session Attendance Job
 *
 * Post-meeting calculation of attendance from stored webhook events.
 * Runs 5 minutes after session ends to ensure all webhooks received.
 *
 * MULTI-TENANCY: Processes sessions grouped by academy for proper tenant isolation.
 * ShouldBeUnique: prevents overlapping dispatches that cause duplicate UUID failures.
 */
class CalculateSessionAttendance implements ShouldBeUnique, ShouldQueue
{
    use AttendanceCalculatorTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying with exponential backoff.
     */
    public array $backoff = [30, 60, 120];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of seconds the unique lock should be held.
     */
    public int $uniqueFor = 600;

    /**
     * Execute the job.
     *
     * MULTI-TENANCY: Processes sessions grouped by academy for proper tenant isolation.
     */
    public function handle(): void
    {
        Log::info('Starting post-meeting attendance calculation (multi-tenant)');

        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        // Process each academy separately for tenant isolation
        $this->processForEachAcademy(function (Academy $academy) use (&$totalProcessed, &$totalSkipped, &$totalFailed) {
            $processed = 0;
            $skipped = 0;
            $failed = 0;

            $this->processAcademySessions($academy, $processed, $skipped, $failed);

            $totalProcessed += $processed;
            $totalSkipped += $skipped;
            $totalFailed += $failed;

            return [
                'processed' => $processed,
                'skipped' => $skipped,
                'failed' => $failed,
            ];
        });

        Log::info('Post-meeting attendance calculation completed (multi-tenant)', [
            'processed' => $totalProcessed,
            'skipped' => $totalSkipped,
            'failed' => $totalFailed,
        ]);
    }

    /**
     * Process all sessions for a specific academy.
     *
     * Optimized approach: query uncalculated MeetingAttendance records first,
     * then load the associated session. This avoids scanning thousands of sessions
     * that have no pending attendance to calculate.
     */
    private function processAcademySessions(Academy $academy, int &$processed, int &$skipped, int &$failed): void
    {
        $calculationDelayMinutes = config('business.attendance.calculation_delay_minutes', 5);
        $gracePeriod = now()->subMinutes($calculationDelayMinutes);

        // Process each session type
        $sessionTypes = [
            'quran' => QuranSession::class,
            'academic' => AcademicSession::class,
            'interactive' => InteractiveCourseSession::class,
        ];

        foreach ($sessionTypes as $type => $sessionClass) {
            $query = $sessionClass::query();

            // Scope by academy
            if ($type === 'interactive') {
                $query->whereHas('course', fn ($q) => $q->where('academy_id', $academy->id));
            } else {
                $query->where('academy_id', $academy->id);
            }

            // Only sessions that have ended (past grace period)
            $query->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 60) MINUTE) <= ?', [$gracePeriod]);

            // Only ONGOING or recently COMPLETED sessions
            $query->where(function ($q) {
                $q->where('status', SessionStatus::ONGOING->value)
                    ->orWhere(function ($q2) {
                        $q2->where('status', SessionStatus::COMPLETED->value)
                            ->where('scheduled_at', '>=', now()->subDays(7));
                    });
            });

            // Only sessions that have uncalculated attendance records.
            // Use direct table subquery (not scoped relationship) to avoid
            // session_type mismatch between getMeetingType() and stored session_type.
            $table = (new $sessionClass)->getTable();
            $query->whereRaw("EXISTS (SELECT 1 FROM meeting_attendances WHERE meeting_attendances.session_id = {$table}.id AND meeting_attendances.is_calculated = 0)");

            $query->each(function ($session) use (&$processed, &$failed) {
                $attendances = MeetingAttendance::where('session_id', $session->id)
                    ->where('is_calculated', false)
                    ->get();

                foreach ($attendances as $attendance) {
                    try {
                        $this->calculateAttendance($session, $attendance);
                        $processed++;
                    } catch (Exception $e) {
                        Log::error('Failed to calculate attendance', [
                            'session_id' => $session->id,
                            'user_id' => $attendance->user_id,
                            'error' => $e->getMessage(),
                        ]);
                        $failed++;
                    }
                }
            });
        }
    }

    /**
     * Calculate final attendance for a single attendance record
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

        // 🔥 FIX: Calculate total duration from cycles, excluding preparation and buffer time
        $totalMinutes = $this->calculateTotalDuration($cycles, $sessionStart, $sessionEnd);

        // Tolerance time (grace period for late arrival) - configurable, default 15 minutes
        $toleranceMinutes = config('business.attendance.grace_period_minutes', 15);

        // Determine first join time
        $firstJoinTime = $attendance->first_join_time;

        // Calculate attendance percentage (capped at session start time - no preparation time counted)
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
            'attendance_status' => $status->value, // Save enum value
            'attendance_percentage' => round($attendancePercentage, 2),
            'is_calculated' => true,
            'attendance_calculated_at' => now(),
        ]);

        // Sync to session report
        $this->syncToReport($session, $attendance);

        Log::info('Attendance calculated', [
            'session_id' => $session->id,
            'user_id' => $attendance->user_id,
            'status' => $status->value,
            'duration' => $totalMinutes,
            'percentage' => round($attendancePercentage, 2),
        ]);
    }

    /**
     * Calculate total duration from join/leave cycles
     *
     * Only count time within session's original start/end times:
     * - Preparation time (before session start) is excluded
     * - Buffer time (after session end) is excluded
     * - Supports both webhook format and manual format
     *
     * @param  array  $cycles  Join/leave event pairs
     * @param  Carbon  $sessionStart  Session's scheduled start time
     * @param  Carbon  $sessionEnd  Session's calculated end time
     * @return int Total minutes within session bounds
     */
    private function calculateTotalDuration(array $cycles, Carbon $sessionStart, Carbon $sessionEnd): int
    {
        $totalMinutes = 0;
        $lastJoinTime = null;

        foreach ($cycles as $cycle) {
            // Detect format - webhook format vs manual format
            $isWebhookFormat = isset($cycle['type']);
            $isManualFormat = isset($cycle['joined_at']);

            if ($isWebhookFormat) {
                // WEBHOOK FORMAT: ['type' => 'join/leave', 'timestamp' => X]
                if ($cycle['type'] === 'join') {
                    $lastJoinTime = $cycle['timestamp'];
                } elseif ($cycle['type'] === 'leave' && $lastJoinTime) {
                    $joinTime = is_string($lastJoinTime) ? Carbon::parse($lastJoinTime) : $lastJoinTime;
                    $leaveTime = is_string($cycle['timestamp']) ? Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];

                    // Clip join time to session start (ignore preparation time)
                    if ($joinTime->lt($sessionStart)) {
                        $joinTime = $sessionStart->copy();
                    }

                    // Clip leave time to session end (ignore buffer time)
                    if ($leaveTime->gt($sessionEnd)) {
                        $leaveTime = $sessionEnd->copy();
                    }

                    // Only count duration if the clipped times are still valid (join before leave)
                    if ($joinTime->lt($leaveTime)) {
                        $totalMinutes += (int) round($joinTime->diffInMinutes($leaveTime));
                    }

                    $lastJoinTime = null; // Reset for next cycle
                }
            } elseif ($isManualFormat) {
                // MANUAL FORMAT: ['joined_at' => X, 'left_at' => Y, 'duration_minutes' => Z]
                // This is the format used by MeetingAttendance.recordJoin/Leave()
                if (isset($cycle['joined_at']) && isset($cycle['left_at'])) {
                    $joinTime = is_string($cycle['joined_at']) ? Carbon::parse($cycle['joined_at']) : $cycle['joined_at'];
                    $leaveTime = is_string($cycle['left_at']) ? Carbon::parse($cycle['left_at']) : $cycle['left_at'];

                    // Clip join time to session start (ignore preparation time)
                    if ($joinTime->lt($sessionStart)) {
                        $joinTime = $sessionStart->copy();
                    }

                    // Clip leave time to session end (ignore buffer time)
                    if ($leaveTime->gt($sessionEnd)) {
                        $leaveTime = $sessionEnd->copy();
                    }

                    // Only count duration if the clipped times are still valid (join before leave)
                    if ($joinTime->lt($leaveTime)) {
                        $totalMinutes += (int) round($joinTime->diffInMinutes($leaveTime));
                    }
                }
            }
        }

        Log::debug('Calculated total duration from cycles', [
            'cycles_count' => count($cycles),
            'total_minutes' => $totalMinutes,
            'session_duration' => $sessionStart->diffInMinutes($sessionEnd),
            'percentage' => $sessionStart->diffInMinutes($sessionEnd) > 0
                ? ($totalMinutes / $sessionStart->diffInMinutes($sessionEnd)) * 100
                : 0,
        ]);

        return $totalMinutes;
    }

    /**
     * Reconcile open join cycles that have no matching leave.
     *
     * Looks up MeetingAttendanceEvent for a matching closed event,
     * otherwise falls back to session end time.
     */
    private function reconcileOpenCycles(array $cycles, $session, MeetingAttendance $attendance, Carbon $sessionEnd): array
    {
        $hasChanges = false;
        $fallbackLeaveTime = $session->ended_at ?? $sessionEnd;

        // We need to iterate carefully because we may insert elements (array_splice)
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

            // Try to find matching closed event in MeetingAttendanceEvent table
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
                // Insert matching leave after this join
                array_splice($cycles, $i + 1, 0, [[
                    'type' => 'leave',
                    'timestamp' => $leaveTime instanceof Carbon ? $leaveTime->toISOString() : (string) $leaveTime,
                    'auto_reconciled' => true,
                    'reconciled_from' => $closedEvent ? 'event_table' : 'session_end',
                ]]);
                $i += 2; // Skip past both join and new leave
            } elseif ($isOpenManualCycle) {
                $cycles[$i]['left_at'] = $leaveTime instanceof Carbon ? $leaveTime->toISOString() : (string) $leaveTime;
                $cycles[$i]['auto_reconciled'] = true;
                $cycles[$i]['reconciled_from'] = $closedEvent ? 'event_table' : 'session_end';
                $i++;
            }

            $hasChanges = true;

            Log::info('Reconciled open attendance cycle', [
                'session_id' => $session->id,
                'user_id' => $attendance->user_id,
                'cycle_index' => $i,
                'format' => $isOpenWebhookJoin ? 'webhook' : 'manual',
                'leave_source' => $closedEvent ? 'event_table' : 'session_end',
                'leave_time' => $leaveTime instanceof Carbon ? $leaveTime->toISOString() : (string) $leaveTime,
            ]);
        }

        if ($hasChanges) {
            $attendance->update(['join_leave_cycles' => $cycles]);
        }

        return $cycles;
    }

    /**
     * Check if a webhook join event at the given index has a matching leave event after it.
     */
    private function hasMatchingLeave(array $cycles, int $joinIndex): bool
    {
        $joinCycle = $cycles[$joinIndex];
        $participantSid = $joinCycle['participant_sid'] ?? null;

        for ($i = $joinIndex + 1; $i < count($cycles); $i++) {
            $cycle = $cycles[$i];
            if (isset($cycle['type']) && $cycle['type'] === 'leave') {
                // If participant_sid matches or next leave in sequence
                if ($participantSid && isset($cycle['participant_sid']) && $cycle['participant_sid'] === $participantSid) {
                    return true;
                }
                // If no participant_sid, match positionally (next leave after this join)
                if (! $participantSid) {
                    return true;
                }
            }
            // If we hit another join before finding a leave, this join is open
            if (isset($cycle['type']) && $cycle['type'] === 'join') {
                break;
            }
        }

        return false;
    }

    /**
     * Determine attendance status based on join time and duration.
     * Delegates to AttendanceCalculatorTrait::calculateAttendanceStatusEnum().
     *
     * @param  Carbon|null  $firstJoinTime  When user first joined
     * @param  Carbon  $sessionStartTime  Session's scheduled start time
     * @param  int  $sessionDurationMinutes  Total session duration in minutes
     * @param  int  $totalAttendanceMinutes  How long user actually attended
     * @param  int  $toleranceMinutes  Grace period for late arrivals
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
     * Sync calculated attendance to session report
     * Note: Only student attendance records are synced to reports.
     * Teacher attendance records are skipped to prevent phantom student report creation.
     */
    private function syncToReport($session, MeetingAttendance $attendance): void
    {
        try {
            // Skip teacher participants — their attendance should not create student report records
            if (in_array($attendance->user_type, ['teacher', 'quran_teacher', 'academic_teacher'])) {
                Log::debug('Skipping report sync for teacher attendance record', [
                    'session_id' => $session->id,
                    'user_id' => $attendance->user_id,
                    'user_type' => $attendance->user_type,
                ]);

                return;
            }

            // Find the appropriate report model based on session type
            $reportClass = $this->getReportClass($session);

            if (! $reportClass) {
                Log::warning('No report class found for session type', [
                    'session_class' => get_class($session),
                ]);

                return;
            }

            // Find or create report
            $report = $reportClass::firstOrNew([
                'session_id' => $session->id,
                'student_id' => $attendance->user_id,
            ]);

            $teacherId = $session->teacher_id ?? $session->quran_teacher_id ?? null;
            $academyId = $session->academy_id
                ?? $session->course?->academy_id
                ?? $session->quran_circle?->academy_id;

            if (! $academyId) {
                Log::error('Cannot determine academy_id for session report', [
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                ]);

                return;
            }

            // Update report with calculated attendance
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

            Log::info('Attendance synced to report', [
                'session_id' => $session->id,
                'user_id' => $attendance->user_id,
                'report_class' => class_basename($reportClass),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to sync attendance to report', [
                'session_id' => $session->id,
                'user_id' => $attendance->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the appropriate report class for a session
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
        Log::error('CalculateSessionAttendance job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
