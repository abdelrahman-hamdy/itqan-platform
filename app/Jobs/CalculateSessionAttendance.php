<?php

namespace App\Jobs;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Jobs\Traits\TenantAwareJob;
use App\Models\AcademicSession;
use App\Models\Academy;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Calculate Session Attendance Job
 *
 * Post-meeting calculation of attendance from stored webhook events.
 * Runs 5 minutes after session ends to ensure all webhooks received.
 *
 * MULTI-TENANCY: Processes sessions grouped by academy for proper tenant isolation.
 */
class CalculateSessionAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds to wait before retrying with exponential backoff.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Execute the job.
     *
     * MULTI-TENANCY: Processes sessions grouped by academy for proper tenant isolation.
     */
    public function handle(): void
    {
        Log::info('🧮 Starting post-meeting attendance calculation (multi-tenant)');

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
     */
    private function processAcademySessions(Academy $academy, int &$processed, int &$skipped, int &$failed): void
    {
        // Find all sessions that ended recently
        // Use configurable delay to allow session data to finalize
        $calculationDelayMinutes = config('business.attendance.calculation_delay_minutes', 5);
        $gracePeriod = now()->subMinutes($calculationDelayMinutes);

        $chunkSize = 100;

        // Process Quran sessions with chunking - filtered by academy
        QuranSession::where('academy_id', $academy->id)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 60) MINUTE) <= ?', [$gracePeriod])
            ->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ONGOING->value])
            ->where('scheduled_at', '>=', now()->subDays(7))
            ->chunk($chunkSize, function ($sessions) use (&$processed, &$skipped, &$failed) {
                $this->processSessionBatch($sessions, $processed, $skipped, $failed);
            });

        // Process Academic sessions with chunking - filtered by academy
        AcademicSession::where('academy_id', $academy->id)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 60) MINUTE) <= ?', [$gracePeriod])
            ->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ONGOING->value])
            ->where('scheduled_at', '>=', now()->subDays(7))
            ->chunk($chunkSize, function ($sessions) use (&$processed, &$skipped, &$failed) {
                $this->processSessionBatch($sessions, $processed, $skipped, $failed);
            });

        // Process Interactive course sessions with chunking - filtered by academy via course relationship
        if (class_exists(InteractiveCourseSession::class)) {
            InteractiveCourseSession::whereHas('course', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 60) MINUTE) <= ?', [$gracePeriod])
                ->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ONGOING->value])
                ->where('scheduled_at', '>=', now()->subDays(7))
                ->chunk($chunkSize, function ($sessions) use (&$processed, &$skipped, &$failed) {
                    $this->processSessionBatch($sessions, $processed, $skipped, $failed);
                });
        }
    }

    /**
     * Process a batch of sessions
     */
    private function processSessionBatch($sessions, int &$processed, int &$skipped, int &$failed): void
    {
        foreach ($sessions as $session) {
            // Find all uncalculated attendance records for this session
            $attendances = MeetingAttendance::where('session_id', $session->id)
                ->where('is_calculated', false)
                ->get();

            if ($attendances->isEmpty()) {
                $skipped++;

                continue;
            }

            foreach ($attendances as $attendance) {
                try {
                    $this->calculateAttendance($session, $attendance);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error('Failed to calculate attendance', [
                        'session_id' => $session->id,
                        'user_id' => $attendance->user_id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }
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
        $sessionDuration = $sessionStart->diffInMinutes($sessionEnd);

        // 🔥 FIX: Calculate total duration from cycles, excluding preparation and buffer time
        $totalMinutes = $this->calculateTotalDuration($cycles, $sessionStart, $sessionEnd);

        // Tolerance time (grace period for late arrival) - configurable, default 15 minutes
        $toleranceMinutes = config('business.attendance.grace_period_minutes', 15);
        $graceDeadline = $sessionStart->copy()->addMinutes($toleranceMinutes);

        // Determine if user joined within tolerance
        $firstJoinTime = $attendance->first_join_time;
        $isLate = $firstJoinTime && $firstJoinTime->gt($graceDeadline);

        // Calculate attendance percentage (capped at session start time - no preparation time counted)
        $attendancePercentage = $sessionDuration > 0 ? min(100, ($totalMinutes / $sessionDuration) * 100) : 0;

        // Determine attendance status
        $status = $this->determineAttendanceStatus($firstJoinTime, $isLate, $attendancePercentage);

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
     * Determine attendance status based on join time and duration
     *
     * Logic:
     * - attended: joined before tolerance + stayed >= 50%
     * - late: joined after tolerance + stayed >= 50%
     * - left: stayed < 50% (regardless of join time)
     * - absent: didn't join at all
     */
    private function determineAttendanceStatus($firstJoinTime, bool $isLate, float $percentage): AttendanceStatus
    {
        // Never joined
        if (! $firstJoinTime || $percentage < 1) {
            return AttendanceStatus::ABSENT;
        }

        // Stayed < 50% - left early (regardless of join time)
        if ($percentage < 50) {
            return AttendanceStatus::LEFT;
        }

        // Stayed >= 50% but joined after tolerance - late
        if ($isLate) {
            return AttendanceStatus::LATE;
        }

        // Stayed >= 50% and joined on time - attended
        return AttendanceStatus::ATTENDED;
    }

    /**
     * Sync calculated attendance to session report
     */
    private function syncToReport($session, MeetingAttendance $attendance): void
    {
        try {
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
            $academyId = $session->academy_id ?? $session->quran_circle->academy_id ?? 1; // Default to academy 1

            // Update report with calculated attendance
            $report->fill([
                'teacher_id' => $teacherId,
                'academy_id' => $academyId,
                'meeting_enter_time' => $attendance->first_join_time,
                'meeting_leave_time' => $attendance->last_leave_time,
                'actual_attendance_minutes' => $attendance->total_duration_minutes,
                'attendance_status' => $attendance->attendance_status,
                'attendance_percentage' => $attendance->attendance_percentage,
                'is_late' => $attendance->first_join_time && $attendance->first_join_time->gt($session->scheduled_at->copy()->addMinutes(15)),
                'late_minutes' => $attendance->first_join_time ? max(0, $attendance->first_join_time->diffInMinutes($session->scheduled_at)) : 0,
                'is_calculated' => true,
                'evaluated_at' => now(),
            ]);

            $report->save();

            Log::info('Attendance synced to report', [
                'session_id' => $session->id,
                'user_id' => $attendance->user_id,
                'report_class' => class_basename($reportClass),
            ]);

        } catch (\Exception $e) {
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
        $sessionClass = get_class($session);

        if (str_contains($sessionClass, 'QuranSession')) {
            return \App\Models\StudentSessionReport::class;
        }

        if (str_contains($sessionClass, 'AcademicSession')) {
            return \App\Models\AcademicSessionReport::class;
        }

        if (str_contains($sessionClass, 'InteractiveCourseSession')) {
            return \App\Models\InteractiveSessionReport::class;
        }

        return null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateSessionAttendance job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
