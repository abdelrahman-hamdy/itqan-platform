<?php

namespace App\Models\Traits;

use App\Enums\SessionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CountsTowardsSubscription Trait
 *
 * Provides common functionality for sessions that count towards subscription usage.
 * Used by QuranSession and AcademicSession models to avoid code duplication.
 *
 * DESIGN PATTERN:
 * - Template Method Pattern: Defines algorithm structure, child classes implement specifics
 * - Transaction Safety: Uses DB transactions with row locking to prevent race conditions
 * - Idempotency: subscription_counted flag prevents double-counting
 *
 * RESPONSIBILITIES:
 * - Checking if a session counts towards subscription based on status
 * - Updating subscription usage with proper transaction locking
 * - Preventing double-counting of sessions
 * - Logging subscription counting activities
 *
 * CHILD CLASS REQUIREMENTS:
 * - Must implement getSubscriptionForCounting() to return appropriate subscription
 * - Must have 'subscription_counted' boolean field in database
 * - Must have 'status' field that implements SessionStatus enum
 *
 * TRANSACTION SAFETY:
 * 1. Starts DB transaction
 * 2. Locks session row with lockForUpdate()
 * 3. Checks subscription_counted flag
 * 4. Calls subscription->useSession()
 * 5. Sets subscription_counted = true
 * 6. Commits or rolls back on error
 *
 * USAGE:
 * ```php
 * class QuranSession extends BaseSession
 * {
 *     use CountsTowardsSubscription;
 *
 *     protected function getSubscriptionForCounting() {
 *         return $this->individualCircle?->subscription;
 *     }
 * }
 * ```
 *
 * @see QuranSession Example implementation
 * @see AcademicSession Example implementation
 */
trait CountsTowardsSubscription
{
    /**
     * Check if session counts towards subscription
     *
     * SIMPLIFIED LOGIC (students cannot cancel sessions):
     * - COMPLETED sessions: Always count (student attended)
     * - ABSENT sessions: Always count (student didn't show up)
     * - CANCELLED sessions: NEVER count (only teachers/admins can cancel)
     * - Other statuses: Don't count
     */
    public function countsTowardsSubscription(): bool
    {
        // Only COMPLETED sessions count. CANCELLED/SUSPENDED never count.
        return $this->status === SessionStatus::COMPLETED;
    }

    /**
     * Update subscription usage if this session counts towards subscription
     *
     * This method:
     * 1. Checks if session should count (based on status)
     * 2. Gets the subscription from the appropriate relationship
     * 3. Uses database transaction with row locking to prevent race conditions
     * 4. Checks if session was already counted (prevents double-counting)
     * 5. Calls subscription's useSession() method
     * 6. Marks session as counted
     */
    public function updateSubscriptionUsage(): void
    {
        // Only count towards subscription if session meets criteria (e.g., completed or absent)
        if (! $this->countsTowardsSubscription()) {
            return;
        }

        // Group sessions: process all enrolled students individually
        $groupEnrollments = $this->getGroupEnrollmentsForCounting();
        if ($groupEnrollments !== null) {
            $this->updateGroupSubscriptionUsage($groupEnrollments);

            return;
        }

        // Get subscription from child class's relationship
        $subscription = $this->getSubscriptionForCounting();

        // If no subscription, nothing to update
        if (! $subscription) {
            return;
        }

        // TENANT SAFETY: Verify subscription belongs to same academy as session
        if ($subscription->academy_id !== $this->academy_id) {
            Log::error("Subscription {$subscription->id} (academy: {$subscription->academy_id}) does not match session {$this->id} (academy: {$this->academy_id})");

            return;
        }

        // Use database transaction with row locking to prevent race conditions
        DB::transaction(function () use ($subscription) {
            // Lock the session row for update to prevent concurrent updates
            $session = static::lockForUpdate()->find($this->id);

            if (! $session) {
                throw new Exception("Session {$this->id} not found");
            }

            // Check if this session was already counted
            $alreadyCounted = $session->subscription_counted ?? false;

            if (! $alreadyCounted) {
                try {
                    // Deduct one session from subscription (useSession() acquires its own lock)
                    $subscription->useSession();

                    // Mark this session as counted to prevent double-counting
                    $session->update(['subscription_counted' => true]);

                    // Sync MeetingAttendance flag so supervisor UI shows correct state.
                    // Only update NULL flags (respect admin overrides and auto-excluded cases).
                    if (method_exists($session, 'meetingAttendances')) {
                        $session->meetingAttendances()
                            ->where('user_type', 'student')
                            ->whereNull('counts_for_subscription_set_by')
                            ->whereNull('counts_for_subscription')
                            ->update([
                                'counts_for_subscription' => true,
                                'subscription_counted_at' => now(),
                            ]);
                    }

                    // Refresh the current instance with updated data
                    $this->refresh();

                    Log::info("Session {$this->session_code} ({$this->id}) counted towards subscription {$subscription->id}");

                } catch (Exception $e) {
                    Log::warning("Failed to update subscription usage for session {$this->session_code} ({$this->id}): ".$e->getMessage());
                    throw $e; // Re-throw to rollback the transaction
                }
            }
        });
    }

    /**
     * Check if this session was already counted in subscription
     */
    public function isSubscriptionCounted(): bool
    {
        return $this->subscription_counted ?? false;
    }

    /**
     * Reverse subscription usage when session is cancelled
     * Only reverses if session was previously counted
     *
     * This method:
     * 1. Checks if session was already counted
     * 2. Gets the subscription from the appropriate relationship
     * 3. Uses database transaction with row locking
     * 4. Calls subscription's returnSession() method
     * 5. Marks session as not counted
     */
    public function reverseSubscriptionUsage(): void
    {
        // Only reverse if session was already counted
        if (! $this->isSubscriptionCounted()) {
            Log::info("Session {$this->id} was not counted, skipping reversal");

            return;
        }

        $subscription = $this->getSubscriptionForCounting();

        if (! $subscription) {
            Log::info("Session {$this->id} has no subscription, skipping reversal");

            return;
        }

        // TENANT SAFETY: Verify subscription belongs to same academy as session
        if ($subscription->academy_id !== $this->academy_id) {
            Log::error("Subscription {$subscription->id} (academy: {$subscription->academy_id}) does not match session {$this->id} (academy: {$this->academy_id})");

            return;
        }

        DB::transaction(function () use ($subscription) {
            // Lock the session row for update to prevent concurrent updates
            $session = static::lockForUpdate()->find($this->id);

            if (! $session || ! $session->subscription_counted) {
                return;
            }

            try {
                // Reverse the subscription count
                $subscription->returnSession();

                // Mark session as not counted
                $session->update(['subscription_counted' => false]);

                // Refresh the current instance with updated data
                $this->refresh();

                Log::info("Session {$this->session_code} ({$this->id}) subscription usage reversed from subscription {$subscription->id}");

            } catch (Exception $e) {
                Log::warning("Failed to reverse subscription usage for session {$this->session_code} ({$this->id}): ".$e->getMessage());
                throw $e; // Re-throw to rollback the transaction
            }
        });
    }

    /**
     * Update subscription usage for group sessions (circles with multiple students).
     *
     * Unlike updateSubscriptionUsage() which handles a single subscription,
     * this method iterates over all enrolled students and decrements each
     * student's subscription individually.
     *
     * Uses per-student idempotency via subscription_counted_at on attendance records
     * instead of the session-level subscription_counted flag.
     *
     * @param  \Illuminate\Support\Collection  $enrollments  Collection of enrollment models with activeSubscription
     */
    public function updateGroupSubscriptionUsage($enrollments): void
    {
        if (! $this->countsTowardsSubscription()) {
            return;
        }

        // Wrap entire group in single transaction for atomicity.
        // If any student fails, all are rolled back.
        DB::transaction(function () use ($enrollments) {
            foreach ($enrollments as $enrollment) {
                $subscription = $enrollment->activeSubscription;
                if (! $subscription) {
                    continue;
                }

                if ($subscription->academy_id !== $this->academy_id) {
                    Log::error('Group subscription academy mismatch', [
                        'subscription_id' => $subscription->id,
                        'session_id' => $this->id,
                    ]);

                    continue;
                }

                // Lock attendance row to prevent TOCTOU double-counting.
                // Use meetingAttendances (current LiveKit-based table) since the
                // legacy attendances() table (QuranSessionAttendance) is no longer populated.
                $attendance = $this->meetingAttendances()
                    ->where('user_id', $enrollment->student_id)
                    ->where('user_type', 'student')
                    ->lockForUpdate()
                    ->first();

                if ($attendance && $attendance->subscription_counted_at) {
                    continue; // Already counted by another process
                }

                $subscription->useSession();

                if ($attendance) {
                    // Set both the timestamp (idempotency key) and the display flag
                    $attendance->update([
                        'subscription_counted_at' => now(),
                        'counts_for_subscription' => $attendance->counts_for_subscription ?? true,
                    ]);
                }

                Log::info("Group session {$this->id}: subscription {$subscription->id} decremented for student {$enrollment->student_id}");
            }

            // Set session-level flag only after all students processed successfully
            if (! $this->subscription_counted) {
                $this->update(['subscription_counted' => true]);
            }
        });
    }

    /**
     * Get group enrollments for subscription counting.
     *
     * Override in child classes that support group sessions (e.g., QuranSession circles).
     * Returns null for non-group sessions, triggering the individual counting path.
     *
     * @return \Illuminate\Support\Collection|null
     */
    protected function getGroupEnrollmentsForCounting()
    {
        return null;
    }

    /**
     * Get the subscription instance for counting
     *
     * This method must be implemented by the child class to return
     * the appropriate subscription based on the session type.
     *
     * DECOUPLED ARCHITECTURE:
     * - Subscriptions are linked via polymorphic education_unit relationship
     * - Child classes should use the activeSubscription accessor which handles:
     *   - Polymorphic linked subscriptions (new architecture)
     *   - Direct FK relationships (legacy)
     *
     * For QuranSession (individual): return $this->individualCircle?->activeSubscription
     * For QuranSession (group): return enrollment->activeSubscription for the student
     * For AcademicSession: return $this->academicIndividualLesson?->activeSubscription
     *
     * @return QuranSubscription|AcademicSubscription|null
     */
    abstract public function getSubscriptionForCounting();
}
