<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Events\SessionCompletedEvent;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Unified Session Status Service
 *
 * Handles status transitions for ALL session types:
 * - QuranSession (individual and group circles)
 * - AcademicSession (individual lessons)
 * - InteractiveCourseSession (group courses)
 *
 * Delegates to specialized services for:
 * - Settings: SessionSettingsService
 * - Notifications: SessionNotificationService
 */
class UnifiedSessionStatusService
{
    public function __construct(
        protected SessionSettingsService $settingsService,
        protected SessionNotificationService $notificationService
    ) {}

    /**
     * Transition session from SCHEDULED to READY
     * Called when preparation time begins
     */
    public function transitionToReady(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            Log::warning('Cannot transition to READY: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

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
     * Transition session from READY to ONGOING
     * Called when first participant joins the meeting
     */
    public function transitionToOngoing(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::READY) {
            Log::warning('Cannot transition to ONGOING: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        // Check if session has scheduled time
        if (!$session->scheduled_at) {
            Log::warning('Cannot transition to ONGOING: no scheduled time', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
            ]);

            return false;
        }

        // Validate session time has arrived (with early grace period from settings)
        $allowEarlyJoinMinutes = $this->settingsService->getEarlyJoinMinutes($session);
        $earliestJoinTime = $session->scheduled_at->copy()->subMinutes($allowEarlyJoinMinutes);

        if (now()->lt($earliestJoinTime)) {
            Log::warning('Cannot transition to ONGOING: session time has not arrived', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
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
     */
    public function transitionToCompleted(BaseSession $session): bool
    {
        if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            Log::warning('Cannot transition to COMPLETED: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

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
     * Close meeting room when session ends
     */
    protected function closeMeetingRoom(BaseSession $session): void
    {
        if (!$session->meeting_room_name) {
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
     * Transition session to CANCELLED
     * Called when teacher/admin cancels the session
     */
    public function transitionToCancelled(BaseSession $session, ?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (!in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            Log::warning('Cannot transition to CANCELLED: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

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
        if (!$this->settingsService->isIndividualSession($session)) {
            Log::warning('Cannot transition to ABSENT: not an individual session', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
            ]);

            return false;
        }

        if (!in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
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
            'attendance_status' => 'absent',
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
     * Check if session should transition to READY
     */
    public function shouldTransitionToReady(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        if (!$session->scheduled_at) {
            return false;
        }

        $preparationMinutes = $this->settingsService->getPreparationMinutes($session);
        $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);

        // Safety check - don't process sessions too far in future
        $maxFutureTime = now()->addHours($this->settingsService->getMaxFutureHours());
        if ($session->scheduled_at->gt($maxFutureTime)) {
            return false;
        }

        // Also don't process sessions older than 24 hours
        $minPastTime = now()->subHours(24);
        if ($session->scheduled_at->lt($minPastTime)) {
            return false;
        }

        return now()->gte($preparationTime);
    }

    /**
     * Check if session should transition to ABSENT (individual only)
     */
    public function shouldTransitionToAbsent(BaseSession $session): bool
    {
        if (!$this->settingsService->isIndividualSession($session)) {
            return false;
        }

        if (!in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        if (!$session->scheduled_at) {
            return false;
        }

        $graceMinutes = $this->settingsService->getGracePeriodMinutes($session);
        $graceDeadline = $session->scheduled_at->copy()->addMinutes($graceMinutes);

        // Check if no student participation
        $hasStudentParticipation = $session->meetingAttendances()
            ->where('user_type', 'student')
            ->where('total_duration_minutes', '>', 0)
            ->exists();

        return now()->gt($graceDeadline) && !$hasStudentParticipation;
    }

    /**
     * Check if session should auto-complete
     */
    public function shouldAutoComplete(BaseSession $session): bool
    {
        if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return false;
        }

        if (!$session->scheduled_at) {
            return false;
        }

        $endingBufferMinutes = $this->settingsService->getBufferMinutes($session);
        $durationMinutes = $session->duration_minutes ?? 60;
        $autoCompleteTime = $session->scheduled_at
            ->copy()
            ->addMinutes($durationMinutes)
            ->addMinutes($endingBufferMinutes);

        return now()->gte($autoCompleteTime);
    }

    /**
     * Process status transitions for a collection of sessions
     */
    public function processStatusTransitions(Collection $sessions): array
    {
        $results = [
            'transitions_to_ready' => 0,
            'transitions_to_absent' => 0,
            'transitions_to_completed' => 0,
            'errors' => [],
        ];

        foreach ($sessions as $session) {
            try {
                // Check for READY transition
                if ($this->shouldTransitionToReady($session)) {
                    if ($this->transitionToReady($session)) {
                        $results['transitions_to_ready']++;
                        continue;
                    }
                }

                // Check for ABSENT transition FIRST (individual sessions only)
                if ($this->shouldTransitionToAbsent($session)) {
                    if ($this->transitionToAbsent($session)) {
                        $results['transitions_to_absent']++;
                        continue;
                    }
                }
                // Only check for auto-completion if session is not absent
                elseif ($this->shouldAutoComplete($session)) {
                    if ($this->transitionToCompleted($session)) {
                        $results['transitions_to_completed']++;
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'session_type' => $this->settingsService->getSessionType($session),
                    'error' => $e->getMessage(),
                ];

                Log::error('Error processing session status transition', [
                    'session_id' => $session->id,
                    'session_type' => $this->settingsService->getSessionType($session),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Calculate actual session duration
     */
    protected function calculateActualDuration(BaseSession $session): int
    {
        if (!$session->started_at) {
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
            if ($studentReport->attendance_status === 'absent') {
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

        if ($studentAttendance && $studentAttendance->attendance_status === 'absent') {
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
        if (!$this->settingsService->isIndividualSession($session) || !$session->student_id) {
            return;
        }

        $attendance = $session->meetingAttendances()
            ->where('user_id', $session->student_id)
            ->first();

        if (!$attendance) {
            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $session->student_id,
                'user_type' => 'student',
                'session_type' => $this->settingsService->isIndividualSession($session) ? 'individual' : 'group',
                'attendance_status' => 'absent',
                'attendance_percentage' => 0,
                'total_duration_minutes' => 0,
                'is_calculated' => true,
                'attendance_calculated_at' => now(),
            ]);
        } else {
            $attendance->update([
                'attendance_status' => 'absent',
                'is_calculated' => true,
                'attendance_calculated_at' => now(),
            ]);
        }
    }
}
