<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Events\SessionCompletedEvent;
use App\Exceptions\SessionOperationException;
use App\Models\BaseSession;
use App\Models\MeetingAttendance;
use App\Models\StudentSessionReport;
use Illuminate\Support\Facades\Log;

/**
 * Session Transition Service
 *
 * Handles individual status transitions for sessions:
 * - SCHEDULED -> READY
 * - READY -> ONGOING
 * - ONGOING/READY -> COMPLETED
 * - SCHEDULED/READY -> CANCELLED
 * - READY/ONGOING -> ABSENT (individual sessions only)
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
        if ($session->status !== SessionStatus::SCHEDULED) {
            Log::warning('Cannot transition to READY: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

            if ($throwOnError) {
                throw SessionOperationException::invalidTransition(
                    $this->settingsService->getSessionType($session),
                    (string) $session->id,
                    $session->status->value,
                    SessionStatus::READY->value
                );
            }

            return false;
        }

        $session->update([
            'status' => SessionStatus::READY,
            'preparation_completed_at' => now(),
        ]);

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
        $sessionType = $this->settingsService->getSessionType($session);

        if ($session->status !== SessionStatus::READY) {
            Log::warning('Cannot transition to ONGOING: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'current_status' => $session->status->value,
            ]);

            if ($throwOnError) {
                throw SessionOperationException::invalidTransition(
                    $sessionType,
                    (string) $session->id,
                    $session->status->value,
                    SessionStatus::ONGOING->value
                );
            }

            return false;
        }

        // Check if session has scheduled time
        if (! $session->scheduled_at) {
            Log::warning('Cannot transition to ONGOING: no scheduled time', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
            ]);

            if ($throwOnError) {
                throw SessionOperationException::missingPrerequisites(
                    $sessionType,
                    (string) $session->id,
                    $session->status->value,
                    'start',
                    ['وقت الجلسة المجدول مطلوب']
                );
            }

            return false;
        }

        // Validate session time has arrived (with early grace period from settings)
        $allowEarlyJoinMinutes = $this->settingsService->getEarlyJoinMinutes($session);
        $earliestJoinTime = $session->scheduled_at->copy()->subMinutes($allowEarlyJoinMinutes);

        if (now()->lt($earliestJoinTime)) {
            Log::warning('Cannot transition to ONGOING: session time has not arrived', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
                'earliest_join_time' => $earliestJoinTime,
            ]);

            return false;
        }

        // Safety check - don't allow transition for sessions too far in future
        $maxFutureHours = $this->settingsService->getMaxFutureHoursOngoing();
        if ($session->scheduled_at->gt(now()->addHours($maxFutureHours))) {
            Log::warning('Cannot transition to ONGOING: session too far in future', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        // Send notifications when session starts
        $this->notificationService->sendStartedNotifications($session);

        Log::info('Session transitioned to ONGOING', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
            'started_at' => now(),
        ]);

        return true;
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
        $sessionType = $this->settingsService->getSessionType($session);

        if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            Log::warning('Cannot transition to COMPLETED: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'current_status' => $session->status->value,
            ]);

            if ($throwOnError) {
                if ($session->status === SessionStatus::COMPLETED) {
                    throw SessionOperationException::alreadyCompleted(
                        $sessionType,
                        (string) $session->id,
                        'complete'
                    );
                } elseif ($session->status === SessionStatus::CANCELLED) {
                    throw SessionOperationException::sessionCancelled(
                        $sessionType,
                        (string) $session->id,
                        'complete',
                        $session->cancellation_reason
                    );
                } else {
                    throw SessionOperationException::invalidTransition(
                        $sessionType,
                        (string) $session->id,
                        $session->status->value,
                        SessionStatus::COMPLETED->value
                    );
                }
            }

            return false;
        }

        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
            'actual_duration_minutes' => $this->calculateActualDuration($session),
        ]);

        // Dispatch event to finalize attendance (decoupled from MeetingAttendanceService)
        SessionCompletedEvent::dispatch($session, $this->settingsService->getSessionType($session));

        Log::info('SessionCompletedEvent dispatched for attendance finalization', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
        ]);

        // Close the LiveKit meeting room to prevent new joins
        $this->closeMeetingRoom($session);

        // Handle subscription counting for individual sessions
        if ($this->settingsService->isIndividualSession($session)) {
            $this->handleIndividualSessionCompletion($session);
        }

        // Send notifications when session completes
        $this->notificationService->sendCompletedNotifications($session);

        Log::info('Session transitioned to COMPLETED', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
            'ended_at' => now(),
            'actual_duration' => $session->actual_duration_minutes,
        ]);

        return true;
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
        $sessionType = $this->settingsService->getSessionType($session);

        if (! in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            Log::warning('Cannot transition to CANCELLED: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'current_status' => $session->status->value,
            ]);

            if ($throwOnError) {
                if ($session->status === SessionStatus::COMPLETED) {
                    throw SessionOperationException::alreadyCompleted(
                        $sessionType,
                        (string) $session->id,
                        'cancel'
                    );
                } elseif ($session->status === SessionStatus::CANCELLED) {
                    throw SessionOperationException::sessionCancelled(
                        $sessionType,
                        (string) $session->id,
                        'cancel',
                        $session->cancellation_reason
                    );
                } else {
                    throw SessionOperationException::invalidTransition(
                        $sessionType,
                        (string) $session->id,
                        $session->status->value,
                        SessionStatus::CANCELLED->value
                    );
                }
            }

            return false;
        }

        $session->update([
            'status' => SessionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        // Cancelled sessions don't count towards subscription
        Log::info('Session transitioned to CANCELLED', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        return true;
    }

    /**
     * Transition session to ABSENT (individual sessions only)
     * Called when student doesn't join within grace period
     */
    public function transitionToAbsent(BaseSession $session): bool
    {
        if (! $this->settingsService->isIndividualSession($session)) {
            Log::warning('Cannot transition to ABSENT: not an individual session', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
            ]);

            return false;
        }

        if (! in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            Log::warning('Cannot transition to ABSENT: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::ABSENT,
            'ended_at' => now(),
            'attendance_status' => AttendanceStatus::ABSENT->value,
        ]);

        // Mark as absent in meeting attendance
        $this->recordAbsentStatus($session);

        // Absent sessions count towards subscription (by design)
        if (method_exists($session, 'updateSubscriptionUsage')) {
            $session->updateSubscriptionUsage();
        }

        // Send absent notifications
        $this->notificationService->sendAbsentNotifications($session);

        Log::info('Session transitioned to ABSENT', [
            'session_id' => $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
            'student_id' => $session->student_id ?? null,
        ]);

        return true;
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

        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
     * Handle individual session completion logic
     */
    protected function handleIndividualSessionCompletion(BaseSession $session): void
    {
        // Check StudentSessionReport first (most reliable source)
        $studentReport = StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $session->student_id)
            ->first();

        if ($studentReport) {
            if ($studentReport->attendance_status === AttendanceStatus::ABSENT->value) {
                $session->update(['status' => SessionStatus::ABSENT]);
                Log::info('Individual session marked as ABSENT based on StudentSessionReport', [
                    'session_id' => $session->id,
                    'session_type' => $this->settingsService->getSessionType($session),
                    'student_id' => $session->student_id,
                    'report_status' => $studentReport->attendance_status,
                ]);
            }

            return;
        }

        // Fallback: Check MeetingAttendance only if no StudentSessionReport exists
        $studentAttendance = $session->meetingAttendances()
            ->where('user_id', $session->student_id)
            ->where('user_type', 'student')
            ->first();

        if ($studentAttendance && $studentAttendance->attendance_status === AttendanceStatus::ABSENT->value) {
            $session->update(['status' => SessionStatus::ABSENT]);
            Log::info('Individual session marked as ABSENT based on MeetingAttendance (fallback)', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'student_id' => $session->student_id,
            ]);
        }

        // Update subscription usage for completed sessions
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
