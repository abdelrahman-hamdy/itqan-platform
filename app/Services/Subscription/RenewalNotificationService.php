<?php

namespace App\Services\Subscription;

use App\Models\BaseSubscription;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * RenewalNotificationService
 *
 * Handles all notification logic related to subscription renewals.
 *
 * RESPONSIBILITIES:
 * - Sending renewal reminder notifications (7-day and 3-day)
 * - Sending renewal success notifications
 * - Sending payment failure notifications
 * - Managing notification timing and delivery
 *
 * NOTIFICATION STRATEGY:
 * - Reminders sent 7 days and 3 days before renewal
 * - Success notifications sent immediately after successful renewal
 * - Failure notifications sent immediately when payment fails
 * - All notifications are Arabic-first with proper formatting
 */
class RenewalNotificationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send renewal reminder notification
     */
    public function sendRenewalReminderNotification(BaseSubscription $subscription, int $daysUntilRenewal): void
    {
        $student = $subscription->student;
        if (! $student) {
            return;
        }

        try {
            if (method_exists($subscription, 'sendRenewalReminder')) {
                $subscription->sendRenewalReminder($daysUntilRenewal);

                return;
            }

            $this->notificationService->sendSubscriptionExpiringNotification($student, [
                'subscription_id' => $subscription->id,
                'subscription_type' => class_basename($subscription),
                'name' => $subscription->subscription_code ?? class_basename($subscription),
                'expiry_date' => $subscription->next_billing_date?->format('Y-m-d') ?? '',
                'days_remaining' => $daysUntilRenewal,
                'renewal_amount' => $subscription->calculateRenewalPrice(),
                'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
                'url' => '/subscriptions',
            ]);

            Log::info('Renewal reminder sent', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'days_until_renewal' => $daysUntilRenewal,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send renewal reminder', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send renewal success notification
     */
    public function sendRenewalSuccessNotification(BaseSubscription $subscription, float $amount): void
    {
        $student = $subscription->student;
        if (! $student) {
            return;
        }

        try {
            $this->notificationService->sendSubscriptionRenewedNotification($student, [
                'subscription_id' => $subscription->id,
                'subscription_type' => class_basename($subscription),
                'name' => $subscription->subscription_code ?? class_basename($subscription),
                'amount' => $amount,
                'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
                'next_billing_date' => $subscription->next_billing_date?->format('Y-m-d') ?? '',
                'url' => '/subscriptions',
            ]);

            Log::info('Renewal success notification sent', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send renewal success notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send payment failed notification
     */
    public function sendPaymentFailedNotification(BaseSubscription $subscription, string $reason): void
    {
        $student = $subscription->student;
        if (! $student) {
            return;
        }

        try {
            $this->notificationService->sendPaymentFailedNotification($student, [
                'subscription_id' => $subscription->id,
                'subscription_type' => class_basename($subscription),
                'subscription_name' => $subscription->subscription_code ?? class_basename($subscription),
                'amount' => $subscription->final_price ?? 0,
                'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
                'reason' => $reason,
                'url' => '/subscriptions',
            ]);

            Log::info('Payment failed notification sent', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send payment failed notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
