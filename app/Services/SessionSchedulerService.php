<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\BaseSession;
use App\Models\MeetingAttendanceEvent;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Session Scheduler Service
 *
 * Handles batch processing of sessions for scheduled jobs (cron).
 * Evaluates sessions and determines which transitions are needed based on time checks.
 *
 * Priority order for transitions:
 * 1. SCHEDULED -> READY (if preparation time reached)
 * 2. READY/ONGOING -> ABSENT (if grace period exceeded with no student - individual only)
 * 3. ONGOING -> COMPLETED (if duration + buffer exceeded)
 */
class SessionSchedulerService
{
    public function __construct(
        protected SessionTransitionService $transitionService,
        protected SessionSettingsService $settingsService
    ) {}

    /**
     * Check if session should transition to READY
     */
    public function shouldTransitionToReady(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        if (! $session->scheduled_at) {
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
     *
     * A session is marked ABSENT when:
     * - It's an individual session (not group)
     * - Grace period has passed
     * - Student has no recorded participation
     *
     * Note: ABSENT counts toward subscription as a no-show penalty (confirmed business rule).
     * If teacher also didn't attend, this is logged for admin reporting.
     */
    public function shouldTransitionToAbsent(BaseSession $session): bool
    {
        if (! $this->settingsService->isIndividualSession($session)) {
            return false;
        }

        if (! in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        if (! $session->scheduled_at) {
            return false;
        }

        $graceMinutes = $this->settingsService->getGracePeriodMinutes($session);
        $graceDeadline = $session->scheduled_at->copy()->addMinutes($graceMinutes);

        // SAFETY: Never mark absent if attendance hasn't been calculated yet.
        // The race condition: cron runs before CalculateSessionForAttendance job finishes,
        // sees total_duration_minutes=0, and prematurely marks the session ABSENT.
        $attendances = $session->relationLoaded('meetingAttendances')
            ? $session->meetingAttendances
            : $session->meetingAttendances()->get();

        $studentAttendances = $attendances->where('user_type', 'student');

        // If any student attendance record exists but isn't calculated yet, WAIT — don't mark absent
        if ($studentAttendances->where('is_calculated', false)->isNotEmpty()) {
            return false;
        }

        // If any student has a first_join_time, they DID join — never mark absent
        if ($studentAttendances->whereNotNull('first_join_time')->isNotEmpty()) {
            return false;
        }

        // Fallback: check raw webhook events in case MeetingAttendance failed to create
        if (MeetingAttendanceEvent::where('session_id', $session->id)
            ->where('event_type', 'joined')
            ->exists()) {
            return false;
        }

        // Only mark absent if grace period passed AND no student participation evidence at all
        $hasStudentParticipation = $studentAttendances
            ->where('total_duration_minutes', '>', 0)
            ->isNotEmpty();

        $hasTeacherParticipation = $attendances
            ->where('user_type', 'teacher')
            ->where('total_duration_minutes', '>', 0)
            ->isNotEmpty();

        $shouldMarkAbsent = now()->gt($graceDeadline) && ! $hasStudentParticipation;

        // Log when both teacher and student are absent (for admin review)
        if ($shouldMarkAbsent && ! $hasTeacherParticipation) {
            Log::warning('Session marked ABSENT with no teacher participation', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'scheduled_at' => $session->scheduled_at?->toIso8601String(),
                'grace_deadline' => $graceDeadline->toIso8601String(),
                'note' => 'Both teacher and student absent - review may be needed',
            ]);
        }

        return $shouldMarkAbsent;
    }

    /**
     * Check if session should auto-complete
     */
    public function shouldAutoComplete(BaseSession $session): bool
    {
        if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return false;
        }

        if (! $session->scheduled_at) {
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
     *
     * This is the main entry point for scheduled jobs.
     * Evaluates each session and performs appropriate transitions in priority order.
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
                    if ($this->transitionService->transitionToReady($session)) {
                        $results['transitions_to_ready']++;

                        continue;
                    }
                }

                // Check for ABSENT transition FIRST (individual sessions only)
                if ($this->shouldTransitionToAbsent($session)) {
                    if ($this->transitionService->transitionToAbsent($session)) {
                        $results['transitions_to_absent']++;

                        continue;
                    }
                }
                // Only check for auto-completion if session is not absent
                elseif ($this->shouldAutoComplete($session)) {
                    if ($this->transitionService->transitionToCompleted($session)) {
                        $results['transitions_to_completed']++;
                    }
                }

            } catch (Exception $e) {
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
}
