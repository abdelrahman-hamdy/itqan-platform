<?php

namespace App\Models\Traits;

use App\Enums\SessionStatus;
use Exception;
use App\Models\QuranSubscription;
use App\Models\AcademicSubscription;
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
        // CANCELLED sessions never count (students cannot cancel themselves)
        if ($this->status === SessionStatus::CANCELLED) {
            return false;
        }

        // Use enum's default logic for other statuses
        return $this->status->countsTowardsSubscription();
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
                    // Lock the subscription row to prevent double-spending
                    // Two concurrent processes could both read the same balance before either decrements
                    $lockedSub = $subscription::lockForUpdate()->find($subscription->id);
                    if (! $lockedSub) {
                        throw new Exception("Subscription {$subscription->id} not found during counting");
                    }

                    // Deduct one session from subscription
                    $lockedSub->useSession();

                    // Mark this session as counted to prevent double-counting
                    $session->update(['subscription_counted' => true]);

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
    abstract protected function getSubscriptionForCounting();
}
