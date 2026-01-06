<?php

namespace App\Contracts;

use App\Models\BaseSession;
use Illuminate\Support\Collection;

/**
 * Interface for unified session status management service.
 *
 * Handles status transitions for all session types (Quran, Academic, Interactive).
 */
interface SessionStatusServiceInterface
{
    /**
     * Transition session from SCHEDULED to READY.
     *
     * @param  bool  $throwOnError  When true, throws exception instead of returning false
     * @return bool True if transition successful, false otherwise
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToReady(BaseSession $session, bool $throwOnError = false): bool;

    /**
     * Transition session from READY to ONGOING.
     *
     * @param  bool  $throwOnError  When true, throws exception instead of returning false
     * @return bool True if transition successful, false otherwise
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToOngoing(BaseSession $session, bool $throwOnError = false): bool;

    /**
     * Transition session from ONGOING to COMPLETED.
     *
     * @param  bool  $throwOnError  When true, throws exception instead of returning false
     * @return bool True if transition successful, false otherwise
     *
     * @throws \App\Exceptions\SessionOperationException When transition is invalid and $throwOnError is true
     */
    public function transitionToCompleted(BaseSession $session, bool $throwOnError = false): bool;

    /**
     * Transition session to CANCELLED.
     *
     * @param  string|null  $reason  Cancellation reason
     * @param  int|null  $cancelledBy  User ID who cancelled
     * @param  bool  $throwOnError  When true, throws exception instead of returning false
     * @return bool True if transition successful, false otherwise
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
     * @return bool True if transition successful, false otherwise
     */
    public function transitionToAbsent(BaseSession $session): bool;

    /**
     * Check if session should transition to READY.
     *
     * @return bool True if session is ready to transition
     */
    public function shouldTransitionToReady(BaseSession $session): bool;

    /**
     * Check if session should transition to ABSENT.
     *
     * @return bool True if session should be marked absent
     */
    public function shouldTransitionToAbsent(BaseSession $session): bool;

    /**
     * Check if session should auto-complete.
     *
     * @return bool True if session should be completed
     */
    public function shouldAutoComplete(BaseSession $session): bool;

    /**
     * Process status transitions for a collection of sessions.
     *
     * @return array Results with transition counts and errors
     */
    public function processStatusTransitions(Collection $sessions): array;
}
