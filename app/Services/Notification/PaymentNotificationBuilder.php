<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Models\User;

/**
 * Builds and sends payment-related notifications.
 *
 * Handles notifications for payment success/failure,
 * teacher payouts, and subscription renewals.
 */
class PaymentNotificationBuilder
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
        private readonly NotificationUrlBuilder $urlBuilder
    ) {}

    /**
     * Send payment success notification.
     *
     * @param  User  $user  The user who made the payment
     * @param  array  $paymentData  Payment details
     */
    public function sendPaymentSuccessNotification(User $user, array $paymentData): void
    {
        $actionUrl = $this->urlBuilder->getPaymentUrl($paymentData);

        $this->dispatcher->send(
            $user,
            NotificationType::PAYMENT_SUCCESS,
            [
                'amount' => $paymentData['amount'] ?? 0,
                'currency' => $paymentData['currency'] ?? getCurrencyCode(),
                'description' => $paymentData['description'] ?? '',
            ],
            $actionUrl,
            [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'subscription_id' => $paymentData['subscription_id'] ?? null,
                'subscription_type' => $paymentData['subscription_type'] ?? null,
            ],
            true
        );
    }

    /**
     * Send payment failed notification.
     *
     * @param  User  $user  The user whose payment failed
     * @param  array  $paymentData  Payment details
     */
    public function sendPaymentFailedNotification(User $user, array $paymentData): void
    {
        $this->dispatcher->send(
            $user,
            NotificationType::PAYMENT_FAILED,
            [
                'amount' => $paymentData['amount'] ?? 0,
                'currency' => $paymentData['currency'] ?? getCurrencyCode(),
                'reason' => $paymentData['reason'] ?? '',
                'subscription_name' => $paymentData['subscription_name'] ?? '',
            ],
            $paymentData['url'] ?? $this->urlBuilder->getPaymentUrl($paymentData),
            [
                'subscription_id' => $paymentData['subscription_id'] ?? null,
                'subscription_type' => $paymentData['subscription_type'] ?? null,
            ],
            true
        );
    }

    /**
     * Send payout approved notification to teacher.
     *
     * @param  User  $teacher  The teacher to notify
     * @param  array  $payoutData  Payout details
     */
    public function sendPayoutApprovedNotification(User $teacher, array $payoutData): void
    {
        $this->dispatcher->send(
            $teacher,
            NotificationType::PAYOUT_APPROVED,
            [
                'month' => $payoutData['month'] ?? '',
                'amount' => $payoutData['amount'] ?? 0,
                'currency' => $payoutData['currency'] ?? getCurrencyCode(),
                'payout_code' => $payoutData['payout_code'] ?? '',
            ],
            $this->urlBuilder->getTeacherEarningsUrl($teacher),
            [
                'payout_code' => $payoutData['payout_code'] ?? null,
            ],
            true
        );
    }

    /**
     * Send payout rejected notification to teacher.
     *
     * @param  User  $teacher  The teacher to notify
     * @param  array  $payoutData  Payout details
     */
    public function sendPayoutRejectedNotification(User $teacher, array $payoutData): void
    {
        $this->dispatcher->send(
            $teacher,
            NotificationType::PAYOUT_REJECTED,
            [
                'month' => $payoutData['month'] ?? '',
                'reason' => $payoutData['reason'] ?? '',
            ],
            $this->urlBuilder->getTeacherEarningsUrl($teacher),
            [
                'payout_code' => $payoutData['payout_code'] ?? null,
            ],
            true
        );
    }

    /**
     * Send payout paid notification to teacher.
     *
     * @param  User  $teacher  The teacher to notify
     * @param  array  $payoutData  Payout details
     */
    public function sendPayoutPaidNotification(User $teacher, array $payoutData): void
    {
        $this->dispatcher->send(
            $teacher,
            NotificationType::PAYOUT_PAID,
            [
                'month' => $payoutData['month'] ?? '',
                'amount' => $payoutData['amount'] ?? 0,
                'currency' => $payoutData['currency'] ?? getCurrencyCode(),
                'reference' => $payoutData['reference'] ?? '',
            ],
            $this->urlBuilder->getTeacherEarningsUrl($teacher),
            [
                'payout_code' => $payoutData['payout_code'] ?? null,
                'payment_reference' => $payoutData['reference'] ?? null,
            ],
            true
        );
    }

    /**
     * Send subscription renewed notification.
     *
     * @param  User  $student  The student whose subscription renewed
     * @param  array  $subscriptionData  Subscription details
     */
    public function sendSubscriptionRenewedNotification(User $student, array $subscriptionData): void
    {
        $this->dispatcher->send(
            $student,
            NotificationType::SUBSCRIPTION_RENEWED,
            [
                'subscription_name' => $subscriptionData['name'] ?? '',
                'amount' => $subscriptionData['amount'] ?? 0,
                'currency' => $subscriptionData['currency'] ?? getCurrencyCode(),
                'next_billing_date' => $subscriptionData['next_billing_date'] ?? '',
            ],
            $subscriptionData['url'] ?? $this->urlBuilder->getSubscriptionsUrl($student),
            [
                'subscription_id' => $subscriptionData['subscription_id'] ?? null,
                'subscription_type' => $subscriptionData['subscription_type'] ?? null,
            ],
            true
        );
    }

    /**
     * Send subscription expiring reminder notification.
     *
     * @param  User  $student  The student whose subscription is expiring
     * @param  array  $subscriptionData  Subscription details
     */
    public function sendSubscriptionExpiringNotification(User $student, array $subscriptionData): void
    {
        $this->dispatcher->send(
            $student,
            NotificationType::SUBSCRIPTION_EXPIRING,
            [
                'subscription_name' => $subscriptionData['name'] ?? '',
                'expiry_date' => $subscriptionData['expiry_date'] ?? '',
                'days_remaining' => $subscriptionData['days_remaining'] ?? 0,
                'renewal_amount' => $subscriptionData['renewal_amount'] ?? 0,
                'currency' => $subscriptionData['currency'] ?? getCurrencyCode(),
            ],
            $subscriptionData['url'] ?? $this->urlBuilder->getSubscriptionsUrl($student),
            [
                'subscription_id' => $subscriptionData['subscription_id'] ?? null,
                'subscription_type' => $subscriptionData['subscription_type'] ?? null,
            ],
            true
        );
    }
}
