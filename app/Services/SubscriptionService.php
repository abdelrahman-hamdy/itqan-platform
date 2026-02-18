<?php

namespace App\Services;

use InvalidArgumentException;
use App\Contracts\SubscriptionServiceInterface;
use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionAnalyticsService;
use App\Services\Subscription\SubscriptionCreationService;
use App\Services\Subscription\SubscriptionMaintenanceService;
use App\Services\Subscription\SubscriptionQueryService;
use Illuminate\Database\Eloquent\Collection;

/**
 * SubscriptionService
 *
 * Unified service for managing all subscription types:
 * - QuranSubscription (uses SessionSubscriptionStatus)
 * - AcademicSubscription (uses SessionSubscriptionStatus)
 * - CourseSubscription (uses EnrollmentStatus)
 *
 * Provides a single interface for:
 * - Creating subscriptions with package data snapshotting
 * - Activating subscriptions after payment
 * - Cancelling subscriptions
 * - Retrieving unified subscription lists
 * - Getting subscription statistics
 *
 * DESIGN PATTERN:
 * - Facade Pattern: Single entry point for subscription operations
 * - Factory Method: Creates appropriate subscription types
 * - Repository Pattern: Centralized data access
 *
 * This class is a thin delegate — all logic lives in the Subscription sub-services:
 * @see SubscriptionQueryService
 * @see SubscriptionCreationService
 * @see SubscriptionAnalyticsService
 * @see SubscriptionMaintenanceService
 */
class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Subscription type constants
     */
    public const TYPE_QURAN = 'quran';

    public const TYPE_ACADEMIC = 'academic';

    public const TYPE_COURSE = 'course';

    public function __construct(
        private readonly SubscriptionQueryService $queryService,
        private readonly SubscriptionCreationService $creationService,
        private readonly SubscriptionAnalyticsService $analyticsService,
        private readonly SubscriptionMaintenanceService $maintenanceService,
    ) {}

    /**
     * Get all subscription types
     */
    public function getSubscriptionTypes(): array
    {
        return [
            self::TYPE_QURAN => 'اشتراك قرآن',
            self::TYPE_ACADEMIC => 'اشتراك أكاديمي',
            self::TYPE_COURSE => 'اشتراك دورة',
        ];
    }

    /**
     * Get the model class for a subscription type
     */
    public function getModelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_QURAN => QuranSubscription::class,
            self::TYPE_ACADEMIC => AcademicSubscription::class,
            self::TYPE_COURSE => CourseSubscription::class,
            default => throw new InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }

    /**
     * Check if a subscription type uses session-based status
     */
    public function isSessionBased(string $type): bool
    {
        return in_array($type, [self::TYPE_QURAN, self::TYPE_ACADEMIC]);
    }

    // ========================================
    // SUBSCRIPTION CREATION
    // ========================================

    /**
     * Create a new subscription
     *
     * @param  string  $type  Subscription type (quran, academic, course)
     * @param  array  $data  Subscription data
     */
    public function create(string $type, array $data): BaseSubscription
    {
        return $this->creationService->create($type, $data);
    }

    /**
     * Create a Quran subscription
     */
    public function createQuranSubscription(array $data): QuranSubscription
    {
        return $this->creationService->createQuranSubscription($data);
    }

    /**
     * Create an Academic subscription
     */
    public function createAcademicSubscription(array $data): AcademicSubscription
    {
        return $this->creationService->createAcademicSubscription($data);
    }

    /**
     * Create a Course subscription
     */
    public function createCourseSubscription(array $data): CourseSubscription
    {
        return $this->creationService->createCourseSubscription($data);
    }

    /**
     * Create a trial subscription
     */
    public function createTrialSubscription(string $type, array $data): BaseSubscription
    {
        return $this->creationService->createTrialSubscription($type, $data);
    }

    // ========================================
    // SUBSCRIPTION ACTIVATION
    // ========================================

    /**
     * Activate a subscription after successful payment
     */
    public function activate(BaseSubscription $subscription, ?float $amountPaid = null): BaseSubscription
    {
        return $this->maintenanceService->activate($subscription, $amountPaid);
    }

    // ========================================
    // SUBSCRIPTION CANCELLATION
    // ========================================

    /**
     * Cancel a subscription
     */
    public function cancel(BaseSubscription $subscription, ?string $reason = null): BaseSubscription
    {
        return $this->maintenanceService->cancel($subscription, $reason);
    }

    // ========================================
    // UNIFIED SUBSCRIPTION QUERIES
    // ========================================

    /**
     * Get all subscriptions for a student
     *
     * Returns a unified collection of all subscription types
     */
    public function getStudentSubscriptions(int $studentId, ?int $academyId = null): Collection
    {
        return $this->queryService->getStudentSubscriptions($studentId, $academyId);
    }

    /**
     * Get active subscriptions for a student
     */
    public function getActiveSubscriptions(int $studentId, ?int $academyId = null): Collection
    {
        return $this->queryService->getActiveSubscriptions($studentId, $academyId);
    }

    /**
     * Get all subscriptions for an academy
     *
     * Note: This method accepts SessionSubscriptionStatus for Quran/Academic subscriptions.
     * CourseSubscription uses EnrollmentStatus but we map common statuses.
     */
    public function getAcademySubscriptions(int $academyId, ?SessionSubscriptionStatus $status = null): Collection
    {
        return $this->queryService->getAcademySubscriptions($academyId, $status);
    }

    /**
     * Find a subscription by code
     */
    public function findByCode(string $code): ?BaseSubscription
    {
        return $this->queryService->findByCode($code);
    }

    /**
     * Find a subscription by ID and type
     */
    public function findById(int $id, string $type): ?BaseSubscription
    {
        return $this->queryService->findById($id, $type);
    }

    // ========================================
    // SUBSCRIPTION STATISTICS
    // ========================================

    /**
     * Get subscription statistics for an academy
     */
    public function getAcademyStatistics(int $academyId): array
    {
        return $this->analyticsService->getAcademyStatistics($academyId);
    }

    /**
     * Get student subscription statistics
     */
    public function getStudentStatistics(int $studentId): array
    {
        return $this->analyticsService->getStudentStatistics($studentId);
    }

    // ========================================
    // EXPIRING SUBSCRIPTIONS
    // ========================================

    /**
     * Get subscriptions expiring within N days
     */
    public function getExpiringSoon(int $academyId, int $days = 7): Collection
    {
        return $this->queryService->getExpiringSoon($academyId, $days);
    }

    /**
     * Get subscriptions due for renewal
     */
    public function getDueForRenewal(int $academyId): Collection
    {
        return $this->queryService->getDueForRenewal($academyId);
    }

    // ========================================
    // SUBSCRIPTION SUMMARIES FOR VIEWS
    // ========================================

    /**
     * Get unified subscription summaries for display
     *
     * Returns array of subscription summary data suitable for views
     */
    public function getSubscriptionSummaries(int $studentId, ?int $academyId = null): array
    {
        return $this->queryService->getSubscriptionSummaries($studentId, $academyId);
    }

    /**
     * Get active subscription summaries grouped by type
     */
    public function getActiveSubscriptionsByType(int $studentId, ?int $academyId = null): array
    {
        return $this->queryService->getActiveSubscriptionsByType($studentId, $academyId);
    }

    // ========================================
    // BILLING CYCLE CHANGES
    // ========================================

    /**
     * Change subscription billing cycle
     *
     * Note: Takes effect on next renewal, not immediately
     */
    public function changeBillingCycle(BaseSubscription $subscription, BillingCycle $newCycle): BaseSubscription
    {
        return $this->maintenanceService->changeBillingCycle($subscription, $newCycle);
    }

    /**
     * Toggle auto-renewal
     */
    public function toggleAutoRenewal(BaseSubscription $subscription, bool $enabled): BaseSubscription
    {
        return $this->maintenanceService->toggleAutoRenewal($subscription, $enabled);
    }

    // ========================================
    // DUPLICATE PREVENTION & CLEANUP
    // ========================================

    /**
     * Cancel any existing pending subscriptions for the same combination.
     *
     * This is called before creating a new subscription to ensure only
     * one pending subscription exists per unique combination.
     *
     * @param  string  $type  Subscription type (quran, academic, course)
     * @param  int  $academyId  Academy ID
     * @param  int  $studentId  Student ID
     * @param  array  $keyValues  Fields that identify the unique combination
     * @return int Number of pending subscriptions cancelled
     */
    public function cancelDuplicatePending(
        string $type,
        int $academyId,
        int $studentId,
        array $keyValues
    ): int {
        return $this->creationService->cancelDuplicatePending($type, $academyId, $studentId, $keyValues);
    }

    /**
     * Create a subscription with automatic duplicate handling.
     *
     * 1. Cancels any existing pending subscriptions for the same combination
     * 2. Creates the new subscription with pending status
     *
     * @param  string  $type  Subscription type (quran, academic, course)
     * @param  array  $data  Subscription data
     * @param  array  $duplicateKeyValues  Fields for duplicate detection
     */
    public function createWithDuplicateHandling(
        string $type,
        array $data,
        array $duplicateKeyValues
    ): BaseSubscription {
        return $this->creationService->createWithDuplicateHandling($type, $data, $duplicateKeyValues);
    }

    /**
     * Check if an active or pending subscription exists for a combination.
     *
     * @param  string  $type  Subscription type
     * @param  int  $academyId  Academy ID
     * @param  int  $studentId  Student ID
     * @param  array  $keyValues  Fields for uniqueness check
     * @return array{active: BaseSubscription|null, pending: BaseSubscription|null}
     */
    public function findExistingSubscription(
        string $type,
        int $academyId,
        int $studentId,
        array $keyValues
    ): array {
        return $this->creationService->findExistingSubscription($type, $academyId, $studentId, $keyValues);
    }

    /**
     * Handle payment failure for a subscription.
     *
     * Cancels the subscription and marks payment as failed.
     */
    public function handlePaymentFailure(BaseSubscription $subscription, ?string $reason = null): BaseSubscription
    {
        return $this->maintenanceService->handlePaymentFailure($subscription, $reason);
    }

    /**
     * Get all expired pending subscriptions.
     *
     * @param  int|null  $hours  Hours after which pending is expired (uses config default)
     * @return Collection Collection of all expired pending subscriptions
     */
    public function getExpiredPendingSubscriptions(?int $hours = null): Collection
    {
        return $this->maintenanceService->getExpiredPendingSubscriptions($hours);
    }

    /**
     * Cleanup expired pending subscriptions.
     *
     * @param  int|null  $hours  Hours after which pending is expired
     * @param  bool  $dryRun  If true, only returns count without making changes
     * @return array{cancelled: int, by_type: array}
     */
    public function cleanupExpiredPending(?int $hours = null, bool $dryRun = false): array
    {
        return $this->maintenanceService->cleanupExpiredPending($hours, $dryRun);
    }

    /**
     * Get pending subscriptions count by type.
     *
     * Useful for Filament dashboard widgets.
     *
     * @param  int|null  $academyId  Optional academy filter
     */
    public function getPendingSubscriptionsStats(?int $academyId = null): array
    {
        return $this->analyticsService->getPendingSubscriptionsStats($academyId);
    }
}
