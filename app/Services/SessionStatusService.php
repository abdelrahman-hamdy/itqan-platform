<?php

namespace App\Services;

use App\Models\QuranSession;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SessionStatusService
{
    /**
     * Transition session from SCHEDULED to READY
     * Called when preparation time begins (default: 15 minutes before session)
     */
    public function transitionToReady(QuranSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            Log::warning('Cannot transition to READY: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);
            return false;
        }

        $session->update([
            'status' => SessionStatus::READY,
            'preparation_completed_at' => now(),
        ]);

        Log::info('Session transitioned to READY', [
            'session_id' => $session->id,
            'scheduled_at' => $session->scheduled_at,
        ]);

        return true;
    }

    /**
     * Transition session from READY to ONGOING
     * Called when first participant joins the meeting
     */
    public function transitionToOngoing(QuranSession $session): bool
    {
        if ($session->status !== SessionStatus::READY) {
            Log::warning('Cannot transition to ONGOING: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);
            return false;
        }

        $session->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        Log::info('Session transitioned to ONGOING', [
            'session_id' => $session->id,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Transition session from ONGOING to COMPLETED
     * Called when session naturally ends or teacher marks it complete
     */
    public function transitionToCompleted(QuranSession $session): bool
    {
        if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            Log::warning('Cannot transition to COMPLETED: invalid current status', [
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

        // Handle subscription counting for individual sessions
        if ($session->session_type === 'individual') {
            $this->handleIndividualSessionCompletion($session);
        }

        Log::info('Session transitioned to COMPLETED', [
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
    public function transitionToCancelled(QuranSession $session, ?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (!in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            Log::warning('Cannot transition to CANCELLED: invalid current status', [
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

        // Cancelled sessions don't count towards subscription
        Log::info('Session transitioned to CANCELLED', [
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
    public function transitionToAbsent(QuranSession $session): bool
    {
        if ($session->session_type !== 'individual') {
            Log::warning('Cannot transition to ABSENT: not an individual session', [
                'session_id' => $session->id,
                'session_type' => $session->session_type,
            ]);
            return false;
        }

        if (!in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            Log::warning('Cannot transition to ABSENT: invalid current status', [
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

        // Mark as absent in meeting attendance
        $this->recordAbsentStatus($session);

        // Absent sessions count towards subscription (this is by design)
        $session->updateSubscriptionUsage();

        Log::info('Session transitioned to ABSENT', [
            'session_id' => $session->id,
            'student_id' => $session->student_id,
        ]);

        return true;
    }

    /**
     * Check if session should transition to READY
     */
    public function shouldTransitionToReady(QuranSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        $circle = $this->getCircleForSession($session);
        if (!$circle) {
            return false;
        }

        $preparationMinutes = $circle->preparation_minutes ?? 15;
        $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);

        return now()->gte($preparationTime);
    }

    /**
     * Check if session should transition to ABSENT (individual only)
     */
    public function shouldTransitionToAbsent(QuranSession $session): bool
    {
        if ($session->session_type !== 'individual' || $session->status !== SessionStatus::READY) {
            return false;
        }

        $circle = $this->getCircleForSession($session);
        if (!$circle) {
            return false;
        }

        $graceMinutes = $circle->late_join_grace_period_minutes ?? 15;
        $graceDeadline = $session->scheduled_at->copy()->addMinutes($graceMinutes);

        // Check if no one has joined within grace period
        $hasParticipants = $session->meetingAttendances()
            ->where('total_duration_minutes', '>', 0)
            ->exists();

        return now()->gt($graceDeadline) && !$hasParticipants;
    }

    /**
     * Check if session should auto-complete
     */
    public function shouldAutoComplete(QuranSession $session): bool
    {
        if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return false;
        }

        $circle = $this->getCircleForSession($session);
        if (!$circle) {
            return false;
        }

        $endingBufferMinutes = $circle->ending_buffer_minutes ?? 5;
        $autoCompleteTime = $session->scheduled_at
            ->copy()
            ->addMinutes($session->duration_minutes)
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
                    }
                }

                // Check for ABSENT transition (individual sessions only)
                if ($this->shouldTransitionToAbsent($session)) {
                    if ($this->transitionToAbsent($session)) {
                        $results['transitions_to_absent']++;
                    }
                }

                // Check for auto-completion
                if ($this->shouldAutoComplete($session)) {
                    if ($this->transitionToCompleted($session)) {
                        $results['transitions_to_completed']++;
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error processing session status transition', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get the appropriate circle (individual or group) for a session
     */
    private function getCircleForSession(QuranSession $session)
    {
        return $session->session_type === 'individual' 
            ? $session->individualCircle 
            : $session->circle;
    }

    /**
     * Calculate actual session duration
     */
    private function calculateActualDuration(QuranSession $session): int
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
    private function handleIndividualSessionCompletion(QuranSession $session): void
    {
        // Check if student attended based on meeting attendance
        $studentAttendance = $session->meetingAttendances()
            ->where('user_id', $session->student_id)
            ->where('user_type', 'student')
            ->first();

        if ($studentAttendance && $studentAttendance->attendance_status === 'absent') {
            // If student was absent, change session status to ABSENT
            $session->update(['status' => SessionStatus::ABSENT]);
            Log::info('Individual session marked as ABSENT due to student absence', [
                'session_id' => $session->id,
                'student_id' => $session->student_id,
            ]);
        }
    }

    /**
     * Record absent status in meeting attendance
     */
    private function recordAbsentStatus(QuranSession $session): void
    {
        if ($session->session_type === 'individual' && $session->student_id) {
            $attendance = $session->meetingAttendances()
                ->where('user_id', $session->student_id)
                ->first();

            if (!$attendance) {
                $student = $session->student;
                if ($student) {
                    MeetingAttendance::create([
                        'session_id' => $session->id,
                        'user_id' => $session->student_id,
                        'user_type' => 'student',
                        'session_type' => 'individual',
                        'attendance_status' => 'absent',
                        'attendance_percentage' => 0,
                        'total_duration_minutes' => 0,
                        'is_calculated' => true,
                        'attendance_calculated_at' => now(),
                    ]);
                }
            } else {
                $attendance->update([
                    'attendance_status' => 'absent',
                    'is_calculated' => true,
                    'attendance_calculated_at' => now(),
                ]);
            }
        }
    }
}