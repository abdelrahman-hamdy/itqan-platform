<?php

namespace App\Models\Traits;

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
     * SMART CANCELLATION LOGIC:
     * - COMPLETED sessions: Always count (student attended)
     * - ABSENT sessions: Always count (student didn't show up)
     * - CANCELLED sessions: Depends on who cancelled:
     *   - teacher/system cancelled: DON'T count (not student's fault)
     *   - student cancelled: DOES count (student's responsibility)
     * - Other statuses: Don't count
     *
     * @return bool
     */
    public function countsTowardsSubscription(): bool
    {
        // Use enum's default logic for non-cancelled statuses
        if ($this->status !== \App\Enums\SessionStatus::CANCELLED) {
            return $this->status->countsTowardsSubscription();
        }

        // SMART CANCELLATION LOGIC for cancelled sessions
        // If cancelled by teacher or system, don't charge the student
        if (in_array($this->cancellation_type, ['teacher', 'system'])) {
            return false; // Don't count towards subscription
        }

        // If cancelled by student, charge the student (their responsibility)
        if ($this->cancellation_type === 'student') {
            return true; // Counts towards subscription
        }

        // Default: if cancellation_type not set or unknown, don't count
        return false;
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
     *
     * @return void
     */
    public function updateSubscriptionUsage(): void
    {
        // Only count towards subscription if session meets criteria (e.g., completed or absent)
        if (!$this->countsTowardsSubscription()) {
            return;
        }

        // Get subscription from child class's relationship
        $subscription = $this->getSubscriptionForCounting();

        // If no subscription, nothing to update
        if (!$subscription) {
            return;
        }

        // Use database transaction with row locking to prevent race conditions
        DB::transaction(function () use ($subscription) {
            // Lock the session row for update to prevent concurrent updates
            $session = static::lockForUpdate()->find($this->id);

            if (!$session) {
                throw new \Exception("Session {$this->id} not found");
            }

            // Check if this session was already counted
            $alreadyCounted = $session->subscription_counted ?? false;

            if (!$alreadyCounted) {
                try {
                    // Deduct one session from subscription
                    $subscription->useSession();

                    // Mark this session as counted to prevent double-counting
                    $session->update(['subscription_counted' => true]);

                    // Refresh the current instance with updated data
                    $this->refresh();

                    Log::info("Session {$this->session_code} ({$this->id}) counted towards subscription {$subscription->id}");

                } catch (\Exception $e) {
                    Log::warning("Failed to update subscription usage for session {$this->session_code} ({$this->id}): " . $e->getMessage());
                    throw $e; // Re-throw to rollback the transaction
                }
            }
        });
    }

    /**
     * Check if this session was already counted in subscription
     *
     * @return bool
     */
    public function isSubscriptionCounted(): bool
    {
        return $this->subscription_counted ?? false;
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
     * @return \App\Models\QuranSubscription|\App\Models\AcademicSubscription|null
     */
    abstract protected function getSubscriptionForCounting();
}
