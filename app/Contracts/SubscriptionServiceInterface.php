<?php

namespace App\Contracts;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Database\Eloquent\Collection;

/**
 * Subscription Service Interface
 *
 * Defines the contract for unified subscription management across all subscription types:
 * - QuranSubscription
 * - AcademicSubscription
 * - CourseSubscription
 *
 * Provides operations for creating, activating, cancelling, and querying subscriptions
 * with a single unified interface.
 */
interface SubscriptionServiceInterface
{
    /**
     * Get all available subscription types.
     *
     * @return array Array of subscription types with their display names
     */
    public function getSubscriptionTypes(): array;

    /**
     * Get the model class for a subscription type.
     *
     * @param string $type Subscription type (quran, academic, course)
     * @return string Fully qualified model class name
     * @throws \InvalidArgumentException If subscription type is unknown
     */
    public function getModelClass(string $type): string;

    /**
     * Create a new subscription.
     *
     * This is the main factory method that creates subscriptions of any type
     * with proper data snapshotting from packages.
     *
     * @param string $type Subscription type (quran, academic, course)
     * @param array $data Subscription data including student_id, package details, etc.
     * @return BaseSubscription The created subscription instance
     */
    public function create(string $type, array $data): BaseSubscription;

    /**
     * Create a Quran subscription.
     *
     * @param array $data Subscription data
     * @return QuranSubscription The created Quran subscription
     */
    public function createQuranSubscription(array $data): QuranSubscription;

    /**
     * Create an Academic subscription.
     *
     * @param array $data Subscription data
     * @return AcademicSubscription The created Academic subscription
     */
    public function createAcademicSubscription(array $data): AcademicSubscription;

    /**
     * Create a Course subscription.
     *
     * @param array $data Subscription data
     * @return CourseSubscription The created Course subscription
     */
    public function createCourseSubscription(array $data): CourseSubscription;

    /**
     * Create a trial subscription.
     *
     * Creates a free trial subscription with zero cost and activated status.
     *
     * @param string $type Subscription type
     * @param array $data Subscription data
     * @return BaseSubscription The created trial subscription
     */
    public function createTrialSubscription(string $type, array $data): BaseSubscription;

    /**
     * Activate a subscription after successful payment.
     *
     * Transitions subscription from PENDING to ACTIVE status and sets payment details.
     *
     * @param BaseSubscription $subscription The subscription to activate
     * @param float|null $amountPaid The amount paid (updates final_price if provided)
     * @return BaseSubscription The refreshed subscription instance
     * @throws \Exception If subscription is not in pending state
     */
    public function activate(BaseSubscription $subscription, ?float $amountPaid = null): BaseSubscription;

    /**
     * Cancel a subscription.
     *
     * Transitions subscription to CANCELLED status with optional cancellation reason.
     *
     * @param BaseSubscription $subscription The subscription to cancel
     * @param string|null $reason Optional cancellation reason
     * @return BaseSubscription The refreshed subscription instance
     * @throws \Exception If subscription cannot be cancelled in current state
     */
    public function cancel(BaseSubscription $subscription, ?string $reason = null): BaseSubscription;

    /**
     * Get all subscriptions for a student.
     *
     * Returns a unified collection of all subscription types for the specified student.
     *
     * @param int $studentId The student ID
     * @param int|null $academyId Optional academy filter
     * @return Collection Collection of all subscriptions, sorted by created_at descending
     */
    public function getStudentSubscriptions(int $studentId, ?int $academyId = null): Collection;

    /**
     * Get active subscriptions for a student.
     *
     * @param int $studentId The student ID
     * @param int|null $academyId Optional academy filter
     * @return Collection Collection of active subscriptions
     */
    public function getActiveSubscriptions(int $studentId, ?int $academyId = null): Collection;

    /**
     * Get all subscriptions for an academy.
     *
     * Note: Uses SessionSubscriptionStatus for Quran/Academic subscriptions.
     * CourseSubscription uses EnrollmentStatus but common statuses are mapped.
     *
     * @param int $academyId The academy ID
     * @param SessionSubscriptionStatus|null $status Optional status filter
     * @return Collection Collection of academy subscriptions
     */
    public function getAcademySubscriptions(int $academyId, ?SessionSubscriptionStatus $status = null): Collection;

    /**
     * Find a subscription by subscription code.
     *
     * Searches across all subscription types using the unique subscription code.
     *
     * @param string $code The subscription code (e.g., QS-1-001, AS-2-003)
     * @return BaseSubscription|null The subscription if found
     */
    public function findByCode(string $code): ?BaseSubscription;

    /**
     * Find a subscription by ID and type.
     *
     * @param int $id The subscription ID
     * @param string $type Subscription type (quran, academic, course)
     * @return BaseSubscription|null The subscription if found
     */
    public function findById(int $id, string $type): ?BaseSubscription;

    /**
     * Get subscription statistics for an academy.
     *
     * Provides comprehensive statistics including:
     * - Total subscriptions by status
     * - Breakdown by subscription type
     * - Total revenue
     *
     * @param int $academyId The academy ID
     * @return array Array of statistical data
     */
    public function getAcademyStatistics(int $academyId): array;

    /**
     * Get subscription statistics for a student.
     *
     * Provides student-specific statistics including:
     * - Total subscriptions count
     * - Active and completed counts
     * - Total amount spent
     * - Breakdown by subscription type
     *
     * @param int $studentId The student ID
     * @return array Array of statistical data
     */
    public function getStudentStatistics(int $studentId): array;

    /**
     * Get subscriptions expiring within N days.
     *
     * Returns subscriptions that will expire soon for renewal reminders.
     *
     * @param int $academyId The academy ID
     * @param int $days Number of days to look ahead (default: 7)
     * @return Collection Collection of expiring subscriptions
     */
    public function getExpiringSoon(int $academyId, int $days = 7): Collection;

    /**
     * Get subscriptions due for renewal.
     *
     * Returns subscriptions that are currently due for automatic renewal.
     *
     * @param int $academyId The academy ID
     * @return Collection Collection of subscriptions due for renewal
     */
    public function getDueForRenewal(int $academyId): Collection;

    /**
     * Get unified subscription summaries for display.
     *
     * Returns formatted subscription data suitable for views and reports.
     *
     * @param int $studentId The student ID
     * @param int|null $academyId Optional academy filter
     * @return array Array of subscription summary data
     */
    public function getSubscriptionSummaries(int $studentId, ?int $academyId = null): array;

    /**
     * Get active subscriptions grouped by type.
     *
     * @param int $studentId The student ID
     * @param int|null $academyId Optional academy filter
     * @return array Array with keys: quran, academic, course
     */
    public function getActiveSubscriptionsByType(int $studentId, ?int $academyId = null): array;

    /**
     * Change subscription billing cycle.
     *
     * Note: Changes take effect on next renewal, not immediately.
     *
     * @param BaseSubscription $subscription The subscription to update
     * @param BillingCycle $newCycle The new billing cycle
     * @return BaseSubscription The refreshed subscription instance
     */
    public function changeBillingCycle(BaseSubscription $subscription, BillingCycle $newCycle): BaseSubscription;

    /**
     * Toggle auto-renewal on or off.
     *
     * @param BaseSubscription $subscription The subscription to update
     * @param bool $enabled Whether auto-renewal should be enabled
     * @return BaseSubscription The refreshed subscription instance
     * @throws \Exception If billing cycle does not support auto-renewal
     */
    public function toggleAutoRenewal(BaseSubscription $subscription, bool $enabled): BaseSubscription;
}
