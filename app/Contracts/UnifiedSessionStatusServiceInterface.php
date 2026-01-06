<?php

namespace App\Contracts;

use App\Models\BaseSession;
use Illuminate\Support\Collection;

/**
 * Unified Session Status Service Interface
 *
 * Defines the contract for unified session status management across all session types:
 * - QuranSession (individual and group circles)
 * - AcademicSession (individual lessons)
 * - InteractiveCourseSession (group courses)
 *
 * Handles the complete session lifecycle with automatic state transitions:
 * SCHEDULED -> READY -> ONGOING -> COMPLETED
 *                           |-> ABSENT (individual sessions only)
 *      |-> CANCELLED
 *
 * Key responsibilities:
 * - Status transitions with validation
 * - Meeting room creation and closure
 * - Attendance tracking integration
 * - Notification dispatching
 * - Subscription usage tracking
 */
interface UnifiedSessionStatusServiceInterface
{
    /**
     * Transition session from SCHEDULED to READY.
     *
     * Called when preparation time begins (typically 5-15 minutes before start).
     * Creates meeting room and sends ready notifications to participants.
     *
     * @param  BaseSession  $session  The session to transition
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     * @return bool True if transition was successful
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToReady(BaseSession $session, bool $throwOnError = false): bool;

    /**
     * Transition session from READY to ONGOING.
     *
     * Called when first participant joins the meeting. Validates that session time
     * has arrived (with early join grace period) and starts attendance tracking.
     *
     * @param  BaseSession  $session  The session to transition
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     * @return bool True if transition was successful
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToOngoing(BaseSession $session, bool $throwOnError = false): bool;

    /**
     * Transition session from ONGOING to COMPLETED.
     *
     * Called when session naturally ends or teacher marks it complete.
     * Finalizes attendance, closes meeting room, updates subscription usage,
     * and sends completion notifications.
     *
     * @param  BaseSession  $session  The session to transition
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     * @return bool True if transition was successful
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToCompleted(BaseSession $session, bool $throwOnError = false): bool;

    /**
     * Transition session to CANCELLED.
     *
     * Called when teacher/admin cancels the session before it starts.
     * Can only cancel SCHEDULED or READY sessions.
     *
     * @param  BaseSession  $session  The session to cancel
     * @param  string|null  $reason  Optional cancellation reason
     * @param  int|null  $cancelledBy  Optional user ID who cancelled
     * @param  bool  $throwOnError  When true, throws SessionOperationException instead of returning false
     * @return bool True if transition was successful
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToCancelled(
        BaseSession $session,
        ?string $reason = null,
        ?int $cancelledBy = null,
        bool $throwOnError = false
    ): bool;

    /**
     * Transition session to ABSENT (individual sessions only).
     *
     * Called when student doesn't join within grace period after scheduled start time.
     * Only applicable to individual sessions. Session still counts towards subscription.
     *
     * @param  BaseSession  $session  The session to mark as absent
     * @return bool True if transition was successful
     */
    public function transitionToAbsent(BaseSession $session): bool;

    /**
     * Check if session should transition to READY.
     *
     * Evaluates whether current time is within the preparation window
     * before session scheduled start time.
     *
     * @param  BaseSession  $session  The session to check
     * @return bool True if session should transition to READY now
     */
    public function shouldTransitionToReady(BaseSession $session): bool;

    /**
     * Check if session should transition to ABSENT (individual only).
     *
     * For individual sessions, checks if grace period has expired and student
     * has not participated.
     *
     * @param  BaseSession  $session  The session to check
     * @return bool True if session should be marked as absent
     */
    public function shouldTransitionToAbsent(BaseSession $session): bool;

    /**
     * Check if session should auto-complete.
     *
     * Evaluates whether session has exceeded its scheduled duration plus
     * ending buffer time.
     *
     * @param  BaseSession  $session  The session to check
     * @return bool True if session should auto-complete now
     */
    public function shouldAutoComplete(BaseSession $session): bool;

    /**
     * Process status transitions for a collection of sessions.
     *
     * This is the main entry point for scheduled jobs. Evaluates each session
     * and performs appropriate transitions in priority order:
     * 1. SCHEDULED -> READY (if preparation time reached)
     * 2. READY/ONGOING -> ABSENT (if grace period exceeded with no student)
     * 3. ONGOING -> COMPLETED (if duration + buffer exceeded)
     *
     * @param  Collection  $sessions  Collection of BaseSession instances to process
     * @return array Results summary:
     *               - transitions_to_ready: Count of sessions transitioned to READY
     *               - transitions_to_absent: Count of sessions marked as ABSENT
     *               - transitions_to_completed: Count of sessions completed
     *               - errors: Array of error details for failed transitions
     */
    public function processStatusTransitions(Collection $sessions): array;
}
