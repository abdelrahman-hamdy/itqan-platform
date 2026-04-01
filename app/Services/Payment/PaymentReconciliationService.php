<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles unified payment confirmation + subscription activation.
 *
 * Solves the gap where confirming payment on a subscription didn't update the
 * Payment record, and vice versa. This service ensures both are updated atomically.
 */
class PaymentReconciliationService
{
    /**
     * Confirm payment and activate subscription in a single atomic operation.
     *
     * 1. Finds or creates the Payment record
     * 2. Marks Payment as COMPLETED
     * 3. Activates the subscription (handles grace period, dates, linked circles)
     * 4. Logs to PaymentAuditLog
     */
    public function confirmPaymentAndActivate(
        BaseSubscription $subscription,
        ?string $paymentReference = null,
        ?int $paymentId = null,
    ): void {
        DB::transaction(function () use ($subscription, $paymentReference, $paymentId) {
            // Lock the subscription to prevent race conditions
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            // Step 1: Find or create the Payment record
            $payment = $this->resolvePayment($subscription, $paymentId);

            $oldPaymentStatus = $payment->status;

            // Step 2: Mark Payment as COMPLETED (without triggering activateFromPayment)
            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'payment_status' => 'paid',
                'payment_date' => $payment->payment_date ?? now(),
                'paid_at' => $payment->paid_at ?? now(),
                'confirmed_at' => now(),
                'receipt_number' => $payment->receipt_number ?? $payment->generateReceiptNumber(),
                'notes' => $paymentReference
                    ? trim(($payment->notes ?? '')."\n".__('subscriptions.admin_payment_reference').': '.$paymentReference)
                    : $payment->notes,
            ]);

            // Step 3: Activate the subscription
            $updateData = $this->buildSubscriptionActivationData($subscription);

            // Store payment reference in admin notes
            if (! empty($paymentReference)) {
                $note = sprintf(
                    '[%s] %s %s - %s: %s',
                    now()->format('Y-m-d H:i'),
                    __('subscriptions.payment_confirmed_by'),
                    auth()->user()?->name ?? 'System',
                    __('subscriptions.payment_reference'),
                    $paymentReference
                );
                $updateData['admin_notes'] = $subscription->admin_notes
                    ? $subscription->admin_notes."\n\n".$note
                    : $note;
            }

            // Activate linked circle if applicable
            if (($updateData['status'] ?? null) === SessionSubscriptionStatus::ACTIVE
                && $subscription instanceof QuranSubscription
                && $subscription->education_unit_id) {
                $subscription->educationUnit?->update(['is_active' => true]);
            }

            $subscription->update($updateData);

            // Step 4: Audit log
            PaymentAuditLog::logStatusChange(
                $payment,
                $oldPaymentStatus instanceof PaymentStatus ? $oldPaymentStatus->value : (string) $oldPaymentStatus,
                PaymentStatus::COMPLETED->value,
                auth()->id(),
                $paymentReference
                    ? __('subscriptions.admin_confirmed_with_reference', ['reference' => $paymentReference])
                    : __('subscriptions.admin_confirmed_payment')
            );

            Log::info('Payment confirmed and subscription activated via reconciliation', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'subscription_type' => $subscription->getSubscriptionType(),
                'payment_reference' => $paymentReference,
                'confirmed_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Find the pending Payment for a subscription, or create a manual one.
     */
    private function resolvePayment(BaseSubscription $subscription, ?int $paymentId): Payment
    {
        // If a specific payment ID was provided, use it
        if ($paymentId) {
            $payment = $subscription->payments()->find($paymentId);
            if ($payment) {
                return $payment;
            }
        }

        // Find the latest pending payment for this subscription
        $payment = $subscription->payments()
            ->where('status', PaymentStatus::PENDING)
            ->latest()
            ->first();

        if ($payment) {
            return $payment;
        }

        // No pending payment found — create a manual one
        return Payment::createPayment([
            'academy_id' => $subscription->academy_id,
            'user_id' => $subscription->student_id,
            'payable_type' => $subscription::class,
            'payable_id' => $subscription->id,
            'payment_method' => 'cash',
            'payment_gateway' => 'manual',
            'amount' => $subscription->final_price ?? $subscription->getPriceForBillingCycle(),
            'currency' => $subscription->currency ?? 'SAR',
            'status' => PaymentStatus::PENDING,
            'payment_status' => 'pending',
            'notes' => __('subscriptions.manual_payment_created_by_admin'),
        ]);
    }

    /**
     * Build the subscription update data for activation.
     *
     * Handles: PENDING/SUSPENDED/CANCELLED → ACTIVE, grace period recalculation,
     * date resets, and cancellation field clearing.
     */
    private function buildSubscriptionActivationData(BaseSubscription $subscription): array
    {
        $updateData = [
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now(),
        ];

        // If PENDING or CANCELLED, activate the subscription
        if (in_array($subscription->status, [
            SessionSubscriptionStatus::PENDING,
            SessionSubscriptionStatus::CANCELLED,
        ])) {
            $updateData['status'] = SessionSubscriptionStatus::ACTIVE;

            // Clear cancellation fields if reactivating from CANCELLED
            // Do NOT re-enable auto_renew — the student cancelled deliberately
            if ($subscription->status === SessionSubscriptionStatus::CANCELLED) {
                $updateData['cancelled_at'] = null;
                $updateData['cancellation_reason'] = null;
                $updateData['auto_renew'] = false;
            }

            // If no start date or dates expired, reset them
            if (! $subscription->starts_at || $subscription->ends_at?->isPast()) {
                $updateData['starts_at'] = now();
                $updateData['ends_at'] = $subscription->calculateEndDate(now());
            }

            // If grace period was active, calculate new period from ends_at
            $metadata = $subscription->metadata ?? [];
            if (isset($metadata['grace_period_ends_at'])) {
                $updateData['starts_at'] = $subscription->ends_at;
                $updateData['ends_at'] = $subscription->billing_cycle
                    ? $subscription->billing_cycle->calculateEndDate($subscription->ends_at)
                    : ($subscription->ends_at ?? now())->copy()->addMonth();

                // For Academic: sync end_date
                if ($subscription instanceof AcademicSubscription) {
                    $updateData['end_date'] = $updateData['ends_at'];
                }

                // Clear grace period metadata
                unset($metadata['grace_period_ends_at']);
                $updateData['metadata'] = $metadata ?: null;
            }
        }

        return $updateData;
    }
}
