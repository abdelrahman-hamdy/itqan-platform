<?php

namespace App\Observers;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\BaseSubscription;
use App\Services\StudentDashboardService;
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
            $subscription->status = SessionSubscriptionStatus::PENDING;
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
        if (! isset($subscription->auto_renew)) {
            $subscription->auto_renew = $this->shouldAutoRenewByDefault($subscription);
        }

        // Snapshot package data if method exists and data not already set
        if (method_exists($subscription, 'snapshotPackageData') && empty($subscription->package_name_ar)) {
            try {
                $subscription->snapshotPackageData();
            } catch (\Exception $e) {
                Log::warning('Failed to snapshot package data during creation', [
                    'subscription_type' => get_class($subscription),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Calculate dates if not set
        if (empty($subscription->starts_at) && $subscription->payment_status === SubscriptionPaymentStatus::PAID) {
            $subscription->starts_at = now();
        }

        // For lifetime access subscriptions (e.g., recorded courses), don't set ends_at
        // This avoids MySQL datetime overflow issues with 100-year dates
        if (empty($subscription->ends_at) && $subscription->starts_at && $subscription->billing_cycle) {
            // Skip setting ends_at for lifetime access subscriptions
            $hasLifetimeAccess = property_exists($subscription, 'lifetime_access')
                ? $subscription->lifetime_access
                : false;

            if (! $hasLifetimeAccess && $subscription->billing_cycle !== BillingCycle::LIFETIME) {
                $subscription->ends_at = $subscription->billing_cycle->calculateEndDate($subscription->starts_at);
            }
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
        Log::info('Subscription created', [
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'type' => $subscription->getSubscriptionType(),
            'academy_id' => $subscription->academy_id,
            'student_id' => $subscription->student_id,
            'status' => $subscription->status->value ?? $subscription->status,
            'billing_cycle' => $subscription->billing_cycle?->value,
        ]);

        // Clear student dashboard cache so new subscription appears immediately
        $this->clearStudentDashboardCache($subscription);

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

        // Update ended_at when status becomes cancelled
        if ($subscription->isDirty('status')) {
            $newStatus = $subscription->status;
            if ($newStatus === SessionSubscriptionStatus::CANCELLED) {
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

        if (! empty($significantChanges)) {
            Log::info('Subscription updated', [
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
        Log::info('Subscription deleted', [
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
        Log::info('Subscription restored', [
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
        SessionSubscriptionStatus|string|null $oldStatus,
        SessionSubscriptionStatus|string $newStatus
    ): void {
        // Convert string to enum if needed
        if (is_string($oldStatus)) {
            $oldStatus = SessionSubscriptionStatus::tryFrom($oldStatus);
        }
        if (is_string($newStatus)) {
            $newStatus = SessionSubscriptionStatus::tryFrom($newStatus) ?? $newStatus;
        }

        Log::info('Subscription status changed', [
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'old_status' => $oldStatus?->value ?? $oldStatus,
            'new_status' => $newStatus->value ?? $newStatus,
        ]);

        // Broadcast status change
        $this->broadcastStatusChange($subscription, $oldStatus, $newStatus);

        // Handle specific transitions
        if ($newStatus === SessionSubscriptionStatus::ACTIVE && $oldStatus === SessionSubscriptionStatus::PENDING) {
            $this->handleActivation($subscription);
        }

        if ($newStatus === SessionSubscriptionStatus::CANCELLED) {
            $this->handleCancellation($subscription);
        }
    }

    /**
     * Handle subscription activation
     */
    protected function handleActivation(BaseSubscription $subscription): void
    {
        // Log activation
        Log::info('Subscription activated', [
            'subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
        ]);

        // Send activation notification
        $this->sendActivationNotification($subscription);

        // Note: Session creation is handled separately by session scheduling services
    }

    /**
     * Handle subscription cancellation
     */
    protected function handleCancellation(BaseSubscription $subscription): void
    {
        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
            'reason' => $subscription->cancellation_reason,
        ]);

        // Send cancellation notification
        $this->sendCancellationNotification($subscription);

        // Note: Session cancellation is handled separately by session management services
    }

    /**
     * Broadcast status change for real-time updates
     */
    protected function broadcastStatusChange(
        BaseSubscription $subscription,
        SessionSubscriptionStatus|string|null $oldStatus,
        SessionSubscriptionStatus|string $newStatus
    ): void {
        try {
            // Broadcasting not yet implemented - will be added when real-time updates are required
            // event(new SubscriptionStatusChanged($subscription, $oldStatus, $newStatus));
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast subscription status change', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send subscription activation notification
     */
    protected function sendActivationNotification(BaseSubscription $subscription): void
    {
        try {
            // Find related payment to check/track notification status
            $payment = \App\Models\Payment::where('payable_type', get_class($subscription))
                ->where('payable_id', $subscription->id)
                ->where('status', \App\Enums\PaymentStatus::COMPLETED)
                ->latest()
                ->first();

            // Guard against duplicate subscription notifications
            if ($payment && $payment->subscription_notification_sent_at) {
                Log::info('Subscription notification already sent, skipping', [
                    'subscription_id' => $subscription->id,
                    'payment_id' => $payment->id,
                    'sent_at' => $payment->subscription_notification_sent_at,
                ]);
                return;
            }

            $student = $subscription->student;
            if (! $student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Get subscription display name
            $subscriptionName = $this->getSubscriptionName($subscription);
            $subscriptionType = $subscription->getSubscriptionType();

            $notificationService->send(
                $student,
                \App\Enums\NotificationType::SUBSCRIPTION_ACTIVATED,
                [
                    'subscription_name' => $subscriptionName,
                    'subscription_type' => $subscriptionType,
                    'start_date' => $subscription->starts_at?->format('Y-m-d'),
                    'end_date' => $subscription->ends_at?->format('Y-m-d'),
                ],
                $this->getSubscriptionUrl($subscription),
                [
                    'subscription_id' => $subscription->id,
                    'subscription_type' => $subscriptionType,
                ],
                true
            );

            // Mark subscription notification as sent on the payment
            if ($payment) {
                $payment->update(['subscription_notification_sent_at' => now()]);
            }

            Log::info('Subscription activation notification sent', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'payment_id' => $payment?->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send activation notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send subscription expiration notification
     */
    protected function sendExpirationNotification(BaseSubscription $subscription): void
    {
        try {
            $student = $subscription->student;
            if (! $student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Get subscription display name
            $subscriptionName = $this->getSubscriptionName($subscription);
            $subscriptionType = $subscription->getSubscriptionType();

            $notificationService->send(
                $student,
                \App\Enums\NotificationType::SUBSCRIPTION_EXPIRED,
                [
                    'subscription_name' => $subscriptionName,
                    'subscription_type' => $subscriptionType,
                    'expired_date' => $subscription->ends_at?->format('Y-m-d'),
                    'can_renew' => $subscription->canRenew(),
                ],
                $this->getSubscriptionUrl($subscription),
                [
                    'subscription_id' => $subscription->id,
                    'subscription_type' => $subscriptionType,
                ],
                true
            );

            Log::info('Subscription expiration notification sent', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send expiration notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send subscription cancellation notification
     */
    protected function sendCancellationNotification(BaseSubscription $subscription): void
    {
        try {
            $student = $subscription->student;
            if (! $student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Get subscription display name
            $subscriptionName = $this->getSubscriptionName($subscription);
            $subscriptionType = $subscription->getSubscriptionType();

            // Send to student
            $notificationService->send(
                $student,
                \App\Enums\NotificationType::SESSION_CANCELLED,
                [
                    'subscription_name' => $subscriptionName,
                    'subscription_type' => $subscriptionType,
                    'cancellation_reason' => $subscription->cancellation_reason ?? 'غير محدد',
                    'cancelled_at' => $subscription->cancelled_at?->format('Y-m-d H:i'),
                ],
                $this->getSubscriptionUrl($subscription),
                [
                    'subscription_id' => $subscription->id,
                    'subscription_type' => $subscriptionType,
                ],
                true
            );

            Log::info('Subscription cancellation notification sent', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get display name for subscription
     */
    protected function getSubscriptionName(BaseSubscription $subscription): string
    {
        // Try to use snapshot data first
        if (! empty($subscription->package_name_ar)) {
            return $subscription->package_name_ar;
        }

        // Fallback to package relationship
        if ($subscription->package && method_exists($subscription->package, 'name')) {
            return $subscription->package->name ?? 'اشتراك';
        }

        // Default based on type
        return match ($subscription->getSubscriptionType()) {
            'quran' => 'اشتراك القرآن',
            'academic' => 'اشتراك أكاديمي',
            'course' => 'اشتراك الدورة',
            default => 'اشتراك',
        };
    }

    /**
     * Get URL for subscription
     */
    protected function getSubscriptionUrl(BaseSubscription $subscription): string
    {
        $subdomain = $subscription->academy?->subdomain
            ?? \App\Constants\DefaultAcademy::subdomain();

        try {
            return route('student.subscriptions', ['subdomain' => $subdomain]);
        } catch (\Exception $e) {
            return route('student.profile', ['subdomain' => $subdomain]);
        }
    }

    /**
     * Clear student dashboard cache when subscription changes
     *
     * This ensures new subscriptions appear immediately in the student dashboard
     * without waiting for the cache to expire.
     */
    protected function clearStudentDashboardCache(BaseSubscription $subscription): void
    {
        try {
            $studentId = $subscription->student_id;
            $academyId = $subscription->academy_id;

            if (! $studentId || ! $academyId) {
                return;
            }

            $dashboardService = app(StudentDashboardService::class);
            $dashboardService->clearStudentCache($studentId, $academyId);

            Log::debug('Student dashboard cache cleared after subscription change', [
                'subscription_id' => $subscription->id,
                'student_id' => $studentId,
                'academy_id' => $academyId,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to clear student dashboard cache', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
