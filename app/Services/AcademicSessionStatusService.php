<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AcademicSessionStatusService
{
    /**
     * Transition session from SCHEDULED to READY
     * Called when preparation time begins (default: 15 minutes before session)
     */
    public function transitionToReady(AcademicSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            Log::warning('Cannot transition academic session to READY: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::READY,
        ]);

        // Create meeting room when session becomes ready
        $this->createMeetingForSession($session);

        Log::info('Academic session transitioned to READY', [
            'session_id' => $session->id,
            'scheduled_at' => $session->scheduled_at,
        ]);

        return true;
    }

    /**
     * Create meeting room for session when it becomes ready
     */
    private function createMeetingForSession(AcademicSession $session): void
    {
        try {
            // Only create if meeting doesn't already exist
            if ($session->meeting_room_name) {
                Log::info('Meeting already exists for academic session', [
                    'session_id' => $session->id,
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

            Log::info('Meeting created for ready academic session', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
                'meeting_url' => $meetingUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create meeting for ready academic session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Transition session from READY to ONGOING
     * Called when first participant joins the meeting
     */
    public function transitionToOngoing(AcademicSession $session): bool
    {
        if ($session->status !== SessionStatus::READY) {
            Log::warning('Cannot transition academic session to ONGOING: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        // Check if session has scheduled time
        if (! $session->scheduled_at) {
            Log::warning('Cannot transition academic session to ONGOING: no scheduled time', [
                'session_id' => $session->id,
            ]);

            return false;
        }

        // Validate session time has arrived (with 15-minute early grace period)
        $allowEarlyJoinMinutes = 15; // Allow joining 15 minutes early
        $earliestJoinTime = $session->scheduled_at->copy()->subMinutes($allowEarlyJoinMinutes);

        if (now()->lt($earliestJoinTime)) {
            Log::warning('Cannot transition academic session to ONGOING: session time has not arrived', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
                'earliest_join_time' => $earliestJoinTime,
            ]);

            return false;
        }

        // Safety check - don't allow transition for sessions too far in future
        $maxFutureHours = 2; // Don't allow ongoing status for sessions more than 2 hours in future
        if ($session->scheduled_at->gt(now()->addHours($maxFutureHours))) {
            Log::warning('Cannot transition academic session to ONGOING: session too far in future', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        Log::info('Academic session transitioned to ONGOING', [
            'session_id' => $session->id,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Transition session from ONGOING to COMPLETED
     * Called when session naturally ends or teacher marks it complete
     */
    public function transitionToCompleted(AcademicSession $session): bool
    {
        if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            Log::warning('Cannot transition academic session to COMPLETED: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
            'actual_duration_minutes' => $this->calculateActualDuration($session),
        ]);

        // Close the meeting room if it exists
        if ($session->meeting_room_name) {
            try {
                $meetingService = app(\App\Services\AcademicSessionMeetingService::class);
                $meetingService->closeMeeting($session);

                Log::info('Academic session meeting room closed on completion', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to close academic session meeting room on completion', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Academic session transitioned to COMPLETED', [
            'session_id' => $session->id,
            'ended_at' => now(),
            'actual_duration' => $session->actual_duration_minutes,
        ]);

        return true;
    }

    /**
     * Transition session to CANCELLED
     * Called when teacher/admin cancels the session
     */
    public function transitionToCancelled(AcademicSession $session, ?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (! in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            Log::warning('Cannot transition academic session to CANCELLED: invalid current status', [
                'session_id' => $session->id,
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

        Log::info('Academic session transitioned to CANCELLED', [
            'session_id' => $session->id,
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        return true;
    }

    /**
     * Transition session to ABSENT (individual sessions only)
     * Called when student doesn't join within grace period
     */
    public function transitionToAbsent(AcademicSession $session): bool
    {
        if ($session->session_type !== 'individual') {
            Log::warning('Cannot transition academic session to ABSENT: not an individual session', [
                'session_id' => $session->id,
                'session_type' => $session->session_type,
            ]);

            return false;
        }

        if (! in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            Log::warning('Cannot transition academic session to ABSENT: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::ABSENT,
            'ended_at' => now(),
            'attendance_status' => 'absent',
        ]);

        Log::info('Academic session transitioned to ABSENT', [
            'session_id' => $session->id,
            'student_id' => $session->student_id,
        ]);

        return true;
    }

    /**
     * Check if session should transition to READY
     */
    public function shouldTransitionToReady(AcademicSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        // Check if session has scheduled time
        if (! $session->scheduled_at) {
            return false;
        }

        $preparationMinutes = 15; // Default preparation time for academic sessions
        $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);

        // Add safety check - don't process sessions too far in future
        $maxFutureHours = 24; // Don't process sessions more than 24h in future
        $maxFutureTime = now()->addHours($maxFutureHours);

        if ($session->scheduled_at->gt($maxFutureTime)) {
            return false; // Skip sessions too far in future
        }

        // Also don't process sessions older than 24 hours
        $minPastTime = now()->subHours(24);
        if ($session->scheduled_at->lt($minPastTime)) {
            return false; // Skip very old sessions
        }

        return now()->gte($preparationTime);
    }

    /**
     * Check if session should transition to ABSENT (individual only)
     */
    public function shouldTransitionToAbsent(AcademicSession $session): bool
    {
        if ($session->session_type !== 'individual') {
            return false;
        }

        // Check if session is in READY or ONGOING status
        if (! in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        // Check if session has scheduled time
        if (! $session->scheduled_at) {
            return false;
        }

        $graceMinutes = 15; // Default grace period for academic sessions
        $graceDeadline = $session->scheduled_at->copy()->addMinutes($graceMinutes);

        // Check if no student participation (no meeting attendances with duration > 0)
        $hasStudentParticipation = $session->meetingAttendances()
            ->where('user_type', 'student')
            ->where('total_duration_minutes', '>', 0)
            ->exists();

        // Session should be marked absent if:
        // 1. Grace period has passed, AND
        // 2. No student participation
        return now()->gt($graceDeadline) && ! $hasStudentParticipation;
    }

    /**
     * Check if session should auto-complete
     */
    public function shouldAutoComplete(AcademicSession $session): bool
    {
        if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return false;
        }

        // Check if session has scheduled time
        if (! $session->scheduled_at) {
            return false;
        }

        $endingBufferMinutes = 5; // Default ending buffer for academic sessions
        $autoCompleteTime = $session->scheduled_at
            ->copy()
            ->addMinutes($session->duration_minutes ?? 60)
            ->addMinutes($endingBufferMinutes);

        return now()->gte($autoCompleteTime);
    }

    /**
     * Process status transitions for a collection of academic sessions
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
                    }
                }

                // Check for ABSENT transition FIRST (individual sessions only)
                // This must come before auto-completion to prevent completed sessions
                // when student was actually absent
                if ($this->shouldTransitionToAbsent($session)) {
                    if ($this->transitionToAbsent($session)) {
                        $results['transitions_to_absent']++;
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
                    'error' => $e->getMessage(),
                ];

                Log::error('Error processing academic session status transition', [
                    'session_id' => $session->id,
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
    private function calculateActualDuration(AcademicSession $session): int
    {
        if (! $session->started_at) {
            return 0;
        }

        $endTime = $session->ended_at ?? now();

        return $session->started_at->diffInMinutes($endTime);
    }
}
