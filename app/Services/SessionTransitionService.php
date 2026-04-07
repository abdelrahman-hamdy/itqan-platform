<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Events\SessionCompletedEvent;
use App\Exceptions\SessionOperationException;
use App\Models\BaseSession;
use App\Models\MeetingAttendance;
use App\Models\StudentSessionReport;
use App\Models\TeacherEarning;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Session Transition Service
 *
 * Handles individual status transitions for sessions:
 * - SCHEDULED -> READY
 * - READY -> ONGOING
 * - ONGOING/READY -> COMPLETED
 * - SCHEDULED/READY -> CANCELLED
 *
 * Sessions always auto-complete to COMPLETED status. Attendance is tracked
 * separately per user. Financial impact is controlled by counting flags
 * (counts_for_teacher, counts_for_subscription), not by session status.
 *
 * Each transition includes validation, side effects (meeting creation, notifications),
 * and proper error handling.
 */
class SessionTransitionService
{
    public function __construct(
        protected SessionSettingsService $settingsService,
        protected SessionNotificationService $notificationService
    ) {}

    /**
     * Transition session from SCHEDULED to READY
     * Called when preparation time begins
     *
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     *
     * @throws SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToReady(BaseSession $session, bool $throwOnError = false): bool
    {
        return DB::transaction(function () use ($session, $throwOnError) {
            // Lock the session row to prevent concurrent updates
            $lockedSession = $session::lockForUpdate()->find($session->id);

            if (! $lockedSession) {
                Log::warning('Cannot transition to READY: session not found', [
                    'session_id' => $session->id,
                ]);

                return false;
            }

            if ($lockedSession->status !== SessionStatus::SCHEDULED) {
                Log::warning('Cannot transition to READY: invalid current status', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $this->settingsService->getSessionType($lockedSession),
                    'current_status' => $lockedSession->status->value,
                ]);

                if ($throwOnError) {
                    throw SessionOperationException::invalidTransition(
                        $this->settingsService->getSessionType($lockedSession),
                        (string) $lockedSession->id,
                        $lockedSession->status->value,
                        SessionStatus::READY->value
                    );
                }

                return false;
            }

            $lockedSession->update([
                'status' => SessionStatus::READY,
            ]);

            // Refresh the original session instance
            $session->refresh();

            // Create meeting room when session becomes ready
            $this->createMeetingForSession($session);

            // Send notifications to participants
            $this->notificationService->sendReadyNotifications($session);

            Log::info('Session transitioned to READY', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'scheduled_at' => $session->scheduled_at,
            ]);

            return true;
        });
    }

    /**
     * Transition session from READY to ONGOING
     * Called when first participant joins the meeting
     *
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     *
     * @throws SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToOngoing(BaseSession $session, bool $throwOnError = false): bool
    {
        return DB::transaction(function () use ($session, $throwOnError) {
            // Lock the session row to prevent concurrent updates
            $lockedSession = $session::lockForUpdate()->find($session->id);

            if (! $lockedSession) {
                Log::warning('Cannot transition to ONGOING: session not found', [
                    'session_id' => $session->id,
                ]);

                return false;
            }

            $sessionType = $this->settingsService->getSessionType($lockedSession);

            if ($lockedSession->status !== SessionStatus::READY) {
                Log::warning('Cannot transition to ONGOING: invalid current status', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $sessionType,
                    'current_status' => $lockedSession->status->value,
                ]);

                if ($throwOnError) {
                    throw SessionOperationException::invalidTransition(
                        $sessionType,
                        (string) $lockedSession->id,
                        $lockedSession->status->value,
                        SessionStatus::ONGOING->value
                    );
                }

                return false;
            }

            // Check if session has scheduled time
            if (! $lockedSession->scheduled_at) {
                Log::warning('Cannot transition to ONGOING: no scheduled time', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $sessionType,
                ]);

                if ($throwOnError) {
                    throw SessionOperationException::missingPrerequisites(
                        $sessionType,
                        (string) $lockedSession->id,
                        $lockedSession->status->value,
                        'start',
                        ['وقت الجلسة المجدول مطلوب']
                    );
                }

                return false;
            }

            // Validate session time has arrived (with early grace period from settings)
            $allowEarlyJoinMinutes = $this->settingsService->getEarlyJoinMinutes($lockedSession);
            $earliestJoinTime = $lockedSession->scheduled_at->copy()->subMinutes($allowEarlyJoinMinutes);

            if (now()->lt($earliestJoinTime)) {
                Log::warning('Cannot transition to ONGOING: session time has not arrived', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $sessionType,
                    'scheduled_at' => $lockedSession->scheduled_at,
                    'current_time' => now(),
                    'earliest_join_time' => $earliestJoinTime,
                ]);

                return false;
            }

            // Safety check - don't allow transition for sessions too far in future
            $maxFutureHours = $this->settingsService->getMaxFutureHoursOngoing();
            if ($lockedSession->scheduled_at->gt(now()->addHours($maxFutureHours))) {
                Log::warning('Cannot transition to ONGOING: session too far in future', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $sessionType,
                    'scheduled_at' => $lockedSession->scheduled_at,
                    'current_time' => now(),
                ]);

                return false;
            }

            $lockedSession->update([
                'status' => SessionStatus::ONGOING,
                'started_at' => now(),
            ]);

            // Refresh the original session instance
            $session->refresh();

            // Send notifications when session starts
            $this->notificationService->sendStartedNotifications($session);

            Log::info('Session transitioned to ONGOING', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'started_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Transition session from ONGOING to COMPLETED
     * Called when session naturally ends or teacher marks it complete
     *
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     *
     * @throws SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToCompleted(BaseSession $session, bool $throwOnError = false): bool
    {
        return DB::transaction(function () use ($session, $throwOnError) {
            // Lock the session row to prevent concurrent updates
            $lockedSession = $session::lockForUpdate()->find($session->id);

            if (! $lockedSession) {
                Log::warning('Cannot transition to COMPLETED: session not found', [
                    'session_id' => $session->id,
                ]);

                return false;
            }

            $sessionType = $this->settingsService->getSessionType($lockedSession);

            if (! in_array($lockedSession->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
                Log::warning('Cannot transition to COMPLETED: invalid current status', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $sessionType,
                    'current_status' => $lockedSession->status->value,
                ]);

                if ($throwOnError) {
                    if ($lockedSession->status === SessionStatus::COMPLETED) {
                        throw SessionOperationException::alreadyCompleted(
                            $sessionType,
                            (string) $lockedSession->id,
                            'complete'
                        );
                    } elseif ($lockedSession->status === SessionStatus::CANCELLED) {
                        throw SessionOperationException::sessionCancelled(
                            $sessionType,
                            (string) $lockedSession->id,
                            'complete',
                            $lockedSession->cancellation_reason
                        );
                    } else {
                        throw SessionOperationException::invalidTransition(
                            $sessionType,
                            (string) $lockedSession->id,
                            $lockedSession->status->value,
                            SessionStatus::COMPLETED->value
                        );
                    }
                }

                return false;
            }

            $actualDuration = $this->calculateActualDuration($lockedSession);

            $lockedSession->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'actual_duration_minutes' => $actualDuration,
            ]);

            // Refresh the original session instance
            $session->refresh();

            // Dispatch event to finalize attendance (decoupled from MeetingAttendanceService)
            SessionCompletedEvent::dispatch($session, $this->settingsService->getSessionType($session));

            Log::info('SessionCompletedEvent dispatched for attendance finalization', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
            ]);

            // Close the LiveKit meeting room to prevent new joins
            $this->closeMeetingRoom($session);

            // NOTE: Subscription counting for individual sessions is intentionally NOT done here.
            // It is deferred to FinalizeAttendanceListener, which runs after the queued
            // attendance calculation finishes and StudentSessionReport/MeetingAttendance
            // attendance_status fields are populated. Calling it here would always count
            // the subscription immediately because those fields are null at this point.

            // Send notifications when session completes
            $this->notificationService->sendCompletedNotifications($session);

            Log::info('Session transitioned to COMPLETED', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'ended_at' => now(),
                'actual_duration' => $session->actual_duration_minutes,
            ]);

            return true;
        });
    }

    /**
     * Transition session to CANCELLED
     * Called when teacher/admin cancels the session
     *
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     *
     * @throws SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToCancelled(BaseSession $session, ?string $reason = null, ?int $cancelledBy = null, bool $throwOnError = false): bool
    {
        // Resolve the cancellation type BEFORE entering the transaction to avoid
        // User::find() inside a DB lock (which can cause deadlocks with the users table)
        $cancellationType = $this->determineCancellationType($cancelledBy);

        return DB::transaction(function () use ($session, $reason, $cancelledBy, $throwOnError, $cancellationType) {
            // Lock the session row to prevent concurrent updates
            $lockedSession = $session::lockForUpdate()->find($session->id);

            if (! $lockedSession) {
                Log::warning('Cannot transition to CANCELLED: session not found', [
                    'session_id' => $session->id,
                ]);

                return false;
            }

            $sessionType = $this->settingsService->getSessionType($lockedSession);

            if (! in_array($lockedSession->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
                Log::warning('Cannot transition to CANCELLED: invalid current status', [
                    'session_id' => $lockedSession->id,
                    'session_type' => $sessionType,
                    'current_status' => $lockedSession->status->value,
                ]);

                if ($throwOnError) {
                    if ($lockedSession->status === SessionStatus::COMPLETED) {
                        throw SessionOperationException::alreadyCompleted(
                            $sessionType,
                            (string) $lockedSession->id,
                            'cancel'
                        );
                    } elseif ($lockedSession->status === SessionStatus::CANCELLED) {
                        throw SessionOperationException::sessionCancelled(
                            $sessionType,
                            (string) $lockedSession->id,
                            'cancel',
                            $lockedSession->cancellation_reason
                        );
                    } else {
                        throw SessionOperationException::invalidTransition(
                            $sessionType,
                            (string) $lockedSession->id,
                            $lockedSession->status->value,
                            SessionStatus::CANCELLED->value
                        );
                    }
                }

                return false;
            }

            // cancellationType was resolved outside the transaction to avoid User::find() inside a lock
            $lockedSession->update([
                'status' => SessionStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy,
                'cancellation_type' => $cancellationType,
            ]);

            // Refresh the original session instance
            // Note: Side-effects (subscription reversal, remaining sessions update)
            // are handled by session observers when status changes
            $session->refresh();

            Log::info('Session transitioned to CANCELLED', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'reason' => $reason,
                'cancelled_by' => $cancelledBy,
                'cancellation_type' => $cancellationType,
            ]);

            return true;
        });
    }

    /**
     * Determine cancellation type based on who cancelled
     */
    protected function determineCancellationType(?int $cancelledBy): string
    {
        if (! $cancelledBy) {
            return 'system';
        }

        $user = User::find($cancelledBy);
        if (! $user) {
            return 'system';
        }

        // Check user roles to determine type
        if ($user->hasRole([UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return 'teacher';
        }

        if ($user->hasRole([UserType::ADMIN->value, UserType::SUPER_ADMIN->value])) {
            return 'admin';
        }

        return 'system';
    }

    /**
     * Transition session to ABSENT — DEPRECATED.
     *
     * The ABSENT session status has been removed. Sessions now auto-complete
     * to COMPLETED status. Attendance is tracked separately per user via
     * MeetingAttendance/StudentSessionReport. Financial impact is controlled
     * by counting flags (counts_for_teacher, counts_for_subscription).
     *
     * @deprecated Use transitionToCompleted() instead — all sessions auto-complete
     */
    public function transitionToAbsent(BaseSession $session): bool
    {
        Log::warning('transitionToAbsent() called but ABSENT status has been removed. Use transitionToCompleted() instead.', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
        ]);

        return false;
    }

    /**
     * Transition session from ABSENT to FORGIVEN — DEPRECATED.
     *
     * The ABSENT and FORGIVEN session statuses have been removed. Attendance is
     * now tracked separately per user. Financial impact is controlled by counting
     * flags (counts_for_teacher, counts_for_subscription), not by session status.
     *
     * @deprecated ABSENT/FORGIVEN statuses no longer exist
     */
    public function transitionToForgiven(BaseSession $session, string $reason, int $forgivenBy): bool
    {
        Log::warning('transitionToForgiven() called but FORGIVEN status has been removed. Attendance is tracked separately.', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
            'forgiven_by' => $forgivenBy,
        ]);

        return false;
    }

    /**
     * Transition session from ABSENT back to SCHEDULED — DEPRECATED.
     *
     * The ABSENT session status has been removed. Sessions now auto-complete
     * to COMPLETED status. This method is kept as a stub for backward compatibility.
     *
     * @deprecated ABSENT status no longer exists
     */
    public function transitionToScheduledFromAbsent(
        BaseSession $session,
        Carbon $newScheduledAt,
        ?string $reason = null,
        ?int $rescheduledBy = null
    ): bool {
        Log::warning('transitionToScheduledFromAbsent() called but ABSENT status has been removed.', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
        ]);

        return false;
    }

    /**
     * Delete teacher earning for a session (used by forgiveness and absent reschedule).
     */
    protected function deleteTeacherEarning(BaseSession $session, string $action): void
    {
        $earning = TeacherEarning::forSession(get_class($session), $session->id)->first();
        if ($earning) {
            if ($earning->is_disputed) {
                Log::warning("Deleting disputed earning due to {$action}", [
                    'earning_id' => $earning->id,
                    'session_id' => $session->id,
                ]);
            }
            $earning->delete();
        }
    }

    /**
     * Create meeting room for session when it becomes ready
     */
    protected function createMeetingForSession(BaseSession $session): void
    {
        try {
            // Only create if meeting doesn't already exist
            if ($session->meeting_room_name) {
                Log::info('Meeting already exists for session', [
                    'session_id' => $session->id,
                    'session_type' => $this->settingsService->getSessionType($session),
                    'room_name' => $session->meeting_room_name,
                ]);

                return;
            }

            // Use the session's generateMeetingLink method which creates the meeting
            $meetingConfig = $session->getMeetingConfiguration();
            $meetingUrl = $session->generateMeetingLink([
                'max_participants' => $meetingConfig['max_participants'],
                'recording_enabled' => $meetingConfig['recording_enabled'],
            ]);

            Log::info('Meeting created for ready session', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'room_name' => $session->meeting_room_name,
                'meeting_url' => $meetingUrl,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create meeting for ready session', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Close meeting room when session ends
     */
    protected function closeMeetingRoom(BaseSession $session): void
    {
        if (! $session->meeting_room_name) {
            return;
        }

        try {
            $session->endMeeting();

            Log::info('Meeting room closed on session completion', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'room_name' => $session->meeting_room_name,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to close meeting room on completion', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'room_name' => $session->meeting_room_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate actual session duration
     */
    protected function calculateActualDuration(BaseSession $session): int
    {
        if (! $session->started_at) {
            return 0;
        }

        $endTime = $session->ended_at ?? now();

        return $session->started_at->diffInMinutes($endTime);
    }

    /**
     * Handle individual session completion logic.
     * Called from FinalizeAttendanceListener after attendance is finalized so that
     * StudentSessionReport.attendance_status / MeetingAttendance.attendance_status are populated.
     *
     * Sessions remain in COMPLETED status regardless of attendance. Attendance is
     * tracked separately per user via StudentSessionReport/MeetingAttendance.
     * Financial impact is controlled by counting flags (counts_for_teacher,
     * counts_for_subscription).
     */
    public function handleIndividualSessionCompletion(BaseSession $session): void
    {
        // Session stays COMPLETED — attendance is tracked separately.
        // Log the attendance status for auditing purposes.
        $studentReport = StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $session->student_id)
            ->first();

        if ($studentReport && $studentReport->attendance_status === AttendanceStatus::ABSENT) {
            Log::info('Individual session completed with student marked ABSENT in attendance records', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'student_id' => $session->student_id,
                'attendance_status' => $studentReport->attendance_status->value ?? $studentReport->attendance_status,
            ]);
        }

        // Always count subscription usage — the trait's countsTowardsSubscription()
        // checks counting flags (counts_for_subscription) and subscription_counted
        // flag prevents double-counting.
        if (method_exists($session, 'updateSubscriptionUsage')) {
            $session->updateSubscriptionUsage();
        }
    }

    /**
     * Record absent status in meeting attendance
     */
    protected function recordAbsentStatus(BaseSession $session): void
    {
        if (! $this->settingsService->isIndividualSession($session) || ! $session->student_id) {
            return;
        }

        $attendance = $session->meetingAttendances()
            ->where('user_id', $session->student_id)
            ->first();

        if (! $attendance) {
            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $session->student_id,
                'user_type' => 'student',
                'session_type' => $this->settingsService->isIndividualSession($session) ? 'individual' : 'group',
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'attendance_percentage' => 0,
                'total_duration_minutes' => 0,
                'is_calculated' => true,
                'attendance_calculated_at' => now(),
            ]);
        } else {
            $attendance->update([
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'is_calculated' => true,
                'attendance_calculated_at' => now(),
            ]);
        }
    }
}
