<?php

namespace App\Contracts;

use App\DTOs\SessionOperationResult;
use App\Enums\SessionStatus;
use App\Models\User;
use Carbon\Carbon;

/**
 * Interface for session management services.
 *
 * This interface defines the contract for managing sessions across
 * different types (Quran, Academic, Interactive Course).
 */
interface SessionManagerInterface
{
    /**
     * Get the session type managed by this service.
     *
     * @return string The session type identifier ('quran', 'academic', 'interactive')
     */
    public function getSessionType(): string;

    /**
     * Get upcoming sessions for a user.
     *
     * @param  User  $user  The user to get sessions for
     * @param  int  $limit  Maximum number of sessions to return
     * @return array List of upcoming sessions
     */
    public function getUpcomingSessions(User $user, int $limit = 10): array;

    /**
     * Get session by ID.
     *
     * @param  int  $sessionId  The session ID
     * @return mixed The session model or null if not found
     */
    public function getSession(int $sessionId): mixed;

    /**
     * Create a new session.
     *
     * @param  array  $data  The session data
     * @return SessionOperationResult The result of the creation
     */
    public function createSession(array $data): SessionOperationResult;

    /**
     * Update a session.
     *
     * @param  int  $sessionId  The session ID
     * @param  array  $data  The updated data
     * @return SessionOperationResult The result of the update
     */
    public function updateSession(int $sessionId, array $data): SessionOperationResult;

    /**
     * Cancel a session.
     *
     * @param  int  $sessionId  The session ID
     * @param  string|null  $reason  The cancellation reason
     * @param  int|null  $cancelledBy  The user ID who cancelled
     * @return SessionOperationResult The result of the cancellation
     */
    public function cancelSession(int $sessionId, ?string $reason = null, ?int $cancelledBy = null): SessionOperationResult;

    /**
     * Reschedule a session.
     *
     * @param  int  $sessionId  The session ID
     * @param  Carbon  $newTime  The new scheduled time
     * @param  string|null  $reason  The reschedule reason
     * @return SessionOperationResult The result of the reschedule
     */
    public function rescheduleSession(int $sessionId, Carbon $newTime, ?string $reason = null): SessionOperationResult;

    /**
     * Update session status.
     *
     * @param  int  $sessionId  The session ID
     * @param  SessionStatus  $status  The new status
     * @return SessionOperationResult The result of the status change
     */
    public function updateStatus(int $sessionId, SessionStatus $status): SessionOperationResult;

    /**
     * Start a session (mark as ongoing).
     *
     * @param  int  $sessionId  The session ID
     * @return SessionOperationResult The result of starting the session
     */
    public function startSession(int $sessionId): SessionOperationResult;

    /**
     * Complete a session.
     *
     * @param  int  $sessionId  The session ID
     * @param  array  $completionData  Optional completion data
     * @return SessionOperationResult The result of completing the session
     */
    public function completeSession(int $sessionId, array $completionData = []): SessionOperationResult;

    /**
     * Mark a session as absent (student didn't show up).
     *
     * @param  int  $sessionId  The session ID
     * @param  string|null  $reason  The absence reason
     * @return SessionOperationResult The result of marking absent
     */
    public function markAsAbsent(int $sessionId, ?string $reason = null): SessionOperationResult;

    /**
     * Check if a user can manage a session.
     *
     * @param  User  $user  The user to check
     * @param  int  $sessionId  The session ID
     * @return bool Whether the user can manage the session
     */
    public function canUserManageSession(User $user, int $sessionId): bool;

    /**
     * Get session statistics for a user.
     *
     * @param  User  $user  The user
     * @param  Carbon|null  $startDate  Start date for statistics
     * @param  Carbon|null  $endDate  End date for statistics
     * @return array Statistics data
     */
    public function getSessionStatistics(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array;
}
