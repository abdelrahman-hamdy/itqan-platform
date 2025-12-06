<?php

namespace App\Observers;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\BaseSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BaseSubscriptionObserver
 *
 * Handles common lifecycle events for all subscription types.
 * Registered in AppServiceProvider for QuranSubscription, AcademicSubscription, CourseSubscription.
 *
 * RESPONSIBILITIES:
 * - Generate subscription codes on creation
 * - Set default values (status, billing cycle, dates)
 * - Snapshot package data on creation
 * - Log subscription lifecycle events
 * - Broadcast status changes for real-time updates
 *
 * USAGE:
 * Register in AppServiceProvider:
 *   QuranSubscription::observe(BaseSubscriptionObserver::class);
 *   AcademicSubscription::observe(BaseSubscriptionObserver::class);
 *   CourseSubscription::observe(BaseSubscriptionObserver::class);
 */
class BaseSubscriptionObserver
{
    /**
     * Temporary storage for previous status (not persisted to database)
     */
    protected array $previousStatuses = [];

    /**
     * Handle the "creating" event
     *
     * Sets up defaults before subscription is saved to database
     */
    public function creating(BaseSubscription $subscription): void
    {
        // Generate subscription code if not set
        if (empty($subscription->subscription_code)) {
            $subscription->subscription_code = $this->generateSubscriptionCode($subscription);
        }

        // Set default status if not set
        if (empty($subscription->status)) {
            $subscription->status = SubscriptionStatus::PENDING;
        }

        // Set default payment status if not set
        if (empty($subscription->payment_status)) {
            $subscription->payment_status = SubscriptionPaymentStatus::PENDING;
        }

        // Set default billing cycle based on subscription type
        if (empty($subscription->billing_cycle)) {
            $subscription->billing_cycle = $this->getDefaultBillingCycle($subscription);
        }

        // Set default auto_renew based on subscription type
        if (!isset($subscription->auto_renew)) {
            $subscription->auto_renew = $this->shouldAutoRenewByDefault($subscription);
        }

        // Snapshot package data if method exists and data not already set
        if (method_exists($subscription, 'snapshotPackageData') && empty($subscription->package_name_ar)) {
            try {
                $subscription->snapshotPackageData();
            } catch (\Exception $e) {
                Log::warning("Failed to snapshot package data during creation", [
                    'subscription_type' => get_class($subscription),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Calculate dates if not set
        if (empty($subscription->starts_at) && $subscription->payment_status === SubscriptionPaymentStatus::PAID) {
            $subscription->starts_at = now();
        }

        if (empty($subscription->ends_at) && $subscription->starts_at && $subscription->billing_cycle) {
            $subscription->ends_at = $subscription->billing_cycle->calculateEndDate($subscription->starts_at);
        }

        if (empty($subscription->next_billing_date) && $subscription->ends_at && $subscription->auto_renew) {
            $subscription->next_billing_date = $subscription->ends_at;
        }
    }

    /**
     * Handle the "created" event
     *
     * Actions after subscription is saved to database
     */
    public function created(BaseSubscription $subscription): void
    {
        Log::info("Subscription created", [
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'type' => $subscription->getSubscriptionType(),
            'academy_id' => $subscription->academy_id,
            'student_id' => $subscription->student_id,
            'status' => $subscription->status->value ?? $subscription->status,
            'billing_cycle' => $subscription->billing_cycle?->value,
        ]);

        // Broadcast creation event for real-time updates
        $this->broadcastStatusChange($subscription, null, $subscription->status);
    }

    /**
     * Handle the "updating" event
     *
     * Track changes before update is saved
     */
    public function updating(BaseSubscription $subscription): void
    {
        // Track status change for broadcasting (store in observer property, not on model)
        if ($subscription->isDirty('status')) {
            $this->previousStatuses[$subscription->id] = $subscription->getOriginal('status');
        }

        // Update ended_at when status becomes completed or cancelled
        if ($subscription->isDirty('status')) {
            $newStatus = $subscription->status;
            if ($newStatus === SubscriptionStatus::COMPLETED || $newStatus === SubscriptionStatus::CANCELLED) {
                if (empty($subscription->ended_at)) {
                    $subscription->ended_at = now();
                }
            }
        }

        // Recalculate next_billing_date when ends_at changes
        if ($subscription->isDirty('ends_at') && $subscription->auto_renew) {
            $subscription->next_billing_date = $subscription->ends_at;
        }
    }

    /**
     * Handle the "updated" event
     *
     * Actions after update is saved
     */
    public function updated(BaseSubscription $subscription): void
    {
        // Get tracked previous status from observer property
        $previousStatus = $this->previousStatuses[$subscription->id] ?? null;

        if ($previousStatus !== null && $previousStatus !== $subscription->status) {
            $this->handleStatusChange($subscription, $previousStatus, $subscription->status);
        }

        // Clean up tracked status
        unset($this->previousStatuses[$subscription->id]);

        // Log significant changes
        $changes = $subscription->getChanges();
        $significantChanges = array_intersect_key($changes, array_flip([
            'status', 'payment_status', 'auto_renew', 'ends_at', 'cancelled_at',
        ]));

        if (!empty($significantChanges)) {
            Log::info("Subscription updated", [
                'subscription_id' => $subscription->id,
                'subscription_code' => $subscription->subscription_code,
                'changes' => $significantChanges,
            ]);
        }
    }

    /**
     * Handle the "deleted" event
     */
    public function deleted(BaseSubscription $subscription): void
    {
        Log::info("Subscription deleted", [
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'type' => $subscription->getSubscriptionType(),
        ]);
    }

    /**
     * Handle the "restored" event (for soft deletes)
     */
    public function restored(BaseSubscription $subscription): void
    {
        Log::info("Subscription restored", [
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
        ]);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Generate a unique subscription code
     *
     * Format: {TYPE}-{ACADEMY_ID}-{RANDOM}
     * Examples: QRN-5-A3B2C1, ACD-5-X7Y8Z9, CRS-5-M4N5O6
     */
    protected function generateSubscriptionCode(BaseSubscription $subscription): string
    {
        $type = $subscription->getSubscriptionType();
        $prefix = match ($type) {
            'quran' => 'QRN',
            'academic' => 'ACD',
            'course' => 'CRS',
            default => 'SUB',
        };

        $academyId = $subscription->academy_id ?? 0;
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$academyId}-{$random}";
    }

    /**
     * Get default billing cycle based on subscription type
     */
    protected function getDefaultBillingCycle(BaseSubscription $subscription): BillingCycle
    {
        $type = $subscription->getSubscriptionType();

        return match ($type) {
            'quran' => BillingCycle::MONTHLY,
            'academic' => BillingCycle::MONTHLY,
            'course' => BillingCycle::LIFETIME, // Courses are one-time purchase
            default => BillingCycle::MONTHLY,
        };
    }

    /**
     * Determine if subscription should auto-renew by default
     */
    protected function shouldAutoRenewByDefault(BaseSubscription $subscription): bool
    {
        $type = $subscription->getSubscriptionType();

        return match ($type) {
            'quran' => true,    // Quran sessions auto-renew
            'academic' => true, // Academic sessions auto-renew
            'course' => false,  // Courses are one-time purchase
            default => false,
        };
    }

    /**
     * Handle status change logic
     */
    protected function handleStatusChange(
        BaseSubscription $subscription,
        SubscriptionStatus|string|null $oldStatus,
        SubscriptionStatus|string $newStatus
    ): void {
        // Convert string to enum if needed
        if (is_string($oldStatus)) {
            $oldStatus = SubscriptionStatus::tryFrom($oldStatus);
        }
        if (is_string($newStatus)) {
            $newStatus = SubscriptionStatus::tryFrom($newStatus) ?? $newStatus;
        }

        Log::info("Subscription status changed", [
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'old_status' => $oldStatus?->value ?? $oldStatus,
            'new_status' => $newStatus->value ?? $newStatus,
        ]);

        // Broadcast status change
        $this->broadcastStatusChange($subscription, $oldStatus, $newStatus);

        // Handle specific transitions
        if ($newStatus === SubscriptionStatus::ACTIVE && $oldStatus === SubscriptionStatus::PENDING) {
            $this->handleActivation($subscription);
        }

        if ($newStatus === SubscriptionStatus::EXPIRED) {
            $this->handleExpiration($subscription);
        }

        if ($newStatus === SubscriptionStatus::CANCELLED) {
            $this->handleCancellation($subscription);
        }
    }

    /**
     * Handle subscription activation
     */
    protected function handleActivation(BaseSubscription $subscription): void
    {
        // Log activation
        Log::info("Subscription activated", [
            'subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
        ]);

        // TODO: Send activation notification
        // TODO: Create initial sessions if applicable
    }

    /**
     * Handle subscription expiration
     */
    protected function handleExpiration(BaseSubscription $subscription): void
    {
        Log::info("Subscription expired", [
            'subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
            'auto_renew' => $subscription->auto_renew,
        ]);

        // TODO: Send expiration notification
    }

    /**
     * Handle subscription cancellation
     */
    protected function handleCancellation(BaseSubscription $subscription): void
    {
        Log::info("Subscription cancelled", [
            'subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
            'reason' => $subscription->cancellation_reason,
        ]);

        // TODO: Send cancellation notification
        // TODO: Cancel upcoming sessions if applicable
    }

    /**
     * Broadcast status change for real-time updates
     */
    protected function broadcastStatusChange(
        BaseSubscription $subscription,
        SubscriptionStatus|string|null $oldStatus,
        SubscriptionStatus|string $newStatus
    ): void {
        try {
            // TODO: Implement broadcasting when needed
            // event(new SubscriptionStatusChanged($subscription, $oldStatus, $newStatus));
        } catch (\Exception $e) {
            Log::warning("Failed to broadcast subscription status change", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
