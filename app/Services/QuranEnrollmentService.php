<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Payment;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles the Quran subscription enrollment flow:
 * creating subscriptions, payments, and initiating gateway redirect.
 */
class QuranEnrollmentService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentService $paymentService
    ) {}

    /**
     * Create a Quran subscription and initiate payment.
     *
     * @return array{subscription: QuranSubscription, payment: Payment, redirect_url: string|null, error: string|null}
     */
    public function createSubscriptionWithPayment(
        Academy $academy,
        User $user,
        QuranTeacherProfile $teacher,
        QuranPackage $package,
        array $enrollmentData,
        ?string $paymentGateway = null
    ): array {
        $billingCycle = $enrollmentData['billing_cycle'];

        // Calculate the price based on billing cycle
        $price = $package->getPriceForBillingCycle($billingCycle);

        if (! $price) {
            return ['subscription' => null, 'payment' => null, 'redirect_url' => null, 'error' => __('payments.subscription.billing_cycle_unavailable')];
        }

        // Check for existing active subscription
        $duplicateKeyValues = [
            'quran_teacher_id' => $teacher->user_id,
            'package_id' => $package->id,
        ];

        $existing = $this->subscriptionService->findExistingSubscription(
            SubscriptionService::TYPE_QURAN,
            $academy->id,
            $user->id,
            $duplicateKeyValues
        );

        if ($existing['active']) {
            return ['subscription' => null, 'payment' => null, 'redirect_url' => null, 'error' => __('payments.subscription.already_subscribed')];
        }

        // Cancel any existing pending subscriptions for this combination
        $this->subscriptionService->cancelDuplicatePending(
            SubscriptionService::TYPE_QURAN,
            $academy->id,
            $user->id,
            $duplicateKeyValues
        );

        // Calculate dates
        $startDate = now();
        $endDate = match ($billingCycle) {
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };

        $sessionsMultiplier = match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };
        $totalSessions = $package->sessions_per_month * $sessionsMultiplier;

        // Get student profile data
        $studentProfile = $user->studentProfile;
        $studentName = $studentProfile?->full_name ?? $user->name;
        $studentPhone = $studentProfile?->phone ?? $user->phone ?? '';

        // Create subscription
        $subscription = QuranSubscription::create([
            'academy_id' => $academy->id,
            'student_id' => $user->id,
            'quran_teacher_id' => $teacher->user_id,
            'package_id' => $package->id,
            'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
            'subscription_type' => 'individual',
            'total_sessions' => $totalSessions,
            'sessions_used' => 0,
            'sessions_remaining' => $totalSessions,
            'total_price' => $price,
            'discount_amount' => 0,
            'final_price' => $price,
            'currency' => getCurrencyCode(null, $academy),
            'billing_cycle' => $billingCycle,
            'payment_status' => 'pending',
            'status' => 'pending',
            'trial_used' => 0,
            'is_trial_active' => false,
            'memorization_level' => $enrollmentData['current_level'],
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'next_billing_date' => $endDate,
            'auto_renew' => true,
            'progress_percentage' => 0,
            'notes' => $enrollmentData['notes'] ?? null,
            'metadata' => [
                'student_name' => $studentName,
                'student_age' => $studentProfile?->birth_date?->diffInYears(now()),
                'phone' => $studentPhone,
                'email' => $user->email,
                'learning_goals' => $enrollmentData['learning_goals'],
                'preferred_days' => $enrollmentData['preferred_days'] ?? null,
                'preferred_time' => $enrollmentData['preferred_time'] ?? null,
            ],
            'created_by' => $user->id,
        ]);

        // Calculate tax and create payment
        $taxAmount = round($price * 0.15, 2);
        $totalAmount = $price + $taxAmount;

        $paymentSettings = $academy->getPaymentSettings();
        $gateway = $paymentGateway ?? $paymentSettings->getDefaultGateway() ?? config('payments.default', 'paymob');

        $payment = Payment::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'payable_type' => QuranSubscription::class,
            'payable_id' => $subscription->id,
            'payment_code' => 'QSP-'.str_pad($academy->id, 2, '0', STR_PAD_LEFT).'-'.now()->format('ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'payment_method' => $gateway,
            'payment_gateway' => $gateway,
            'payment_type' => 'subscription',
            'amount' => $totalAmount,
            'net_amount' => $price,
            'currency' => getCurrencyCode(null, $academy),
            'tax_amount' => $taxAmount,
            'tax_percentage' => 15,
            'status' => 'pending',
            'payment_status' => 'pending',
            'save_card' => $subscription->auto_renew,
            'created_by' => $user->id,
        ]);

        // Process payment with configured gateway
        $result = $this->paymentService->processPayment($payment, [
            'customer_name' => $studentName,
            'customer_email' => $user->email,
            'customer_phone' => $studentPhone,
            'save_card' => $subscription->auto_renew,
        ]);

        Log::info('Payment service result', [
            'payment_id' => $payment->id,
            'success' => $result['success'] ?? 'not set',
            'has_redirect' => isset($result['redirect_url']),
            'has_iframe' => isset($result['iframe_url']),
        ]);

        // Determine redirect URL
        $redirectUrl = $result['redirect_url'] ?? $result['iframe_url'] ?? null;

        if (! $redirectUrl && ! ($result['success'] ?? false)) {
            // Payment failed immediately - clean up
            $payment->delete();
            $subscription->delete();

            return [
                'subscription' => null,
                'payment' => null,
                'redirect_url' => null,
                'error' => __('payments.subscription.payment_init_failed').': '.($result['error'] ?? __('payments.subscription.unknown_error')),
            ];
        }

        return [
            'subscription' => $subscription,
            'payment' => $payment,
            'redirect_url' => $redirectUrl,
            'error' => null,
        ];
    }
}
