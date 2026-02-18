<?php

namespace App\Services\Circle;

use Exception;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\EnrollmentCapacityException;
use App\Models\Payment;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles the paid circle enrollment flow.
 *
 * Creates a pending subscription and initiates payment gateway redirect.
 * Actual enrollment is completed after successful payment via the webhook.
 *
 * Extracted from CircleEnrollmentService to isolate paid enrollment logic.
 */
class CirclePaidEnrollmentService
{
    /**
     * Create a pending subscription for a paid circle and return Paymob payment redirect URL.
     * The student is NOT enrolled until payment is successful.
     * Uses the same flow as individual subscriptions - directly redirects to Paymob.
     */
    public function createPendingSubscriptionForPayment(User $user, QuranCircle $circle, $academy, ?string $paymentGateway = null): array
    {
        try {
            $result = DB::transaction(function () use ($circle, $user, $academy) {
                // Lock the circle row to check capacity
                $lockedCircle = QuranCircle::lockForUpdate()->find($circle->id);

                // Double-check capacity
                if ($lockedCircle->enrolled_students >= $lockedCircle->max_students) {
                    throw EnrollmentCapacityException::circleFull(
                        circleId: (string) $lockedCircle->id,
                        currentCount: $lockedCircle->enrolled_students,
                        maxCapacity: $lockedCircle->max_students,
                        circleName: $lockedCircle->name ?? null
                    );
                }

                // Check for existing pending subscription for this circle
                $existingSubscription = QuranSubscription::where('student_id', $user->id)
                    ->where('academy_id', $academy->id)
                    ->where('education_unit_id', $lockedCircle->id)
                    ->where('education_unit_type', QuranCircle::class)
                    ->where('subscription_type', 'group')
                    ->where('status', SessionSubscriptionStatus::PENDING)
                    ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                    ->first();

                if ($existingSubscription) {
                    Log::info('[CircleEnrollment] Found existing pending subscription', [
                        'subscription_id' => $existingSubscription->id,
                    ]);

                    return ['subscription' => $existingSubscription, 'is_existing' => true];
                }

                // Create PENDING subscription - student is NOT enrolled yet
                $subscription = QuranSubscription::create([
                    'academy_id' => $academy->id,
                    'student_id' => $user->id,
                    'quran_teacher_id' => $lockedCircle->quran_teacher_id,
                    'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                    'subscription_type' => 'group',
                    'education_unit_id' => $lockedCircle->id,
                    'education_unit_type' => QuranCircle::class,
                    'total_sessions' => $lockedCircle->monthly_sessions_count ?? 8,
                    'sessions_used' => 0,
                    'sessions_remaining' => $lockedCircle->monthly_sessions_count ?? 8,
                    'total_price' => $lockedCircle->monthly_fee,
                    'discount_amount' => 0,
                    'final_price' => $lockedCircle->monthly_fee,
                    'currency' => $lockedCircle->currency ?? getCurrencyCode(null, $academy),
                    'billing_cycle' => 'monthly',
                    'payment_status' => SubscriptionPaymentStatus::PENDING,
                    'status' => SessionSubscriptionStatus::PENDING,
                    'memorization_level' => $lockedCircle->memorization_level ?? 'beginner',
                    'starts_at' => now(),
                    'auto_renew' => true,
                ]);

                Log::info('[CircleEnrollment] Created pending subscription for paid circle', [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                    'payment_status' => $subscription->payment_status,
                ]);

                return ['subscription' => $subscription, 'is_existing' => false, 'circle' => $lockedCircle];
            });

            $subscription = $result['subscription'];
            $lockedCircle = $result['circle'] ?? $circle;

            // Calculate tax (15% VAT) - same as individual subscriptions
            $price = $lockedCircle->monthly_fee;
            $taxAmount = round($price * 0.15, 2);
            $totalAmount = $price + $taxAmount;

            // Get payment gateway (use provided or academy default)
            $paymentSettings = $academy->getPaymentSettings();
            $gateway = $paymentGateway ?? $paymentSettings->getDefaultGateway() ?? config('payments.default', 'paymob');

            // Create payment record - same flow as individual subscriptions
            $payment = Payment::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'payable_type' => QuranSubscription::class,
                'payable_id' => $subscription->id,
                'payment_code' => Payment::generatePaymentCode($academy->id, 'QSP'),
                'payment_method' => $gateway,
                'payment_gateway' => $gateway,
                'payment_type' => 'subscription',
                'amount' => $totalAmount,
                'net_amount' => $price,
                'currency' => $lockedCircle->currency ?? getCurrencyCode(null, $academy),
                'tax_amount' => $taxAmount,
                'tax_percentage' => 15,
                'status' => 'pending',
                'payment_status' => 'pending',
                'save_card' => $subscription->auto_renew,
                'created_by' => $user->id,
            ]);

            Log::info('[CircleEnrollment] Created payment record', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'amount' => $totalAmount,
                'gateway' => $gateway,
            ]);

            // Process payment with configured gateway - get redirect URL (same as individual subscriptions)
            $paymentService = app(PaymentService::class);
            $studentProfile = $user->studentProfile;
            $studentName = $studentProfile?->full_name ?? $user->name ?? 'Student';
            $studentPhone = $studentProfile?->phone ?? $user->phone ?? '';

            $paymentResult = $paymentService->processPayment($payment, [
                'customer_name' => $studentName,
                'customer_email' => $user->email,
                'customer_phone' => $studentPhone,
                'save_card' => $subscription->auto_renew,
            ]);

            Log::info('[CircleEnrollment] Payment service result', [
                'payment_id' => $payment->id,
                'success' => $paymentResult['success'] ?? false,
                'has_redirect' => isset($paymentResult['redirect_url']),
                'has_iframe' => isset($paymentResult['iframe_url']),
            ]);

            // Get the payment gateway URL
            $paymentUrl = $paymentResult['redirect_url'] ?? $paymentResult['iframe_url'] ?? null;

            if (! $paymentUrl) {
                // Payment initiation failed
                Log::error('[CircleEnrollment] Payment initiation failed - no redirect URL', [
                    'payment_id' => $payment->id,
                    'result' => $paymentResult,
                ]);

                // Clean up
                $payment->delete();
                $subscription->delete();

                return [
                    'success' => false,
                    'error' => __('payments.subscription.payment_init_failed').': '.($paymentResult['error'] ?? __('payments.subscription.unknown_error')),
                ];
            }

            return [
                'success' => true,
                'requires_payment' => true,
                'message' => __('circles.payment_required'),
                'subscription' => $subscription,
                'payment' => $payment,
                'payment_url' => $paymentUrl,
            ];
        } catch (EnrollmentCapacityException $e) {
            $e->report();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'capacity_exceeded',
                'available_slots' => $e->getAvailableSlots(),
            ];
        } catch (Exception $e) {
            Log::error('[CircleEnrollment] Error creating pending subscription', [
                'user_id' => $user->id,
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
