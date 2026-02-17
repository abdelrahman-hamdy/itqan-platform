<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Enums\PaymentStatus;
use Exception;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Services\PaymentService;
use App\Services\Subscription\RenewalProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionManualRenewalController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private RenewalProcessor $renewalProcessor
    ) {}

    /**
     * Show manual renewal payment page
     */
    public function show(Request $request, string $type, int $id)
    {
        $subscription = $this->findSubscription($type, $id);

        if (!$subscription) {
            abort(404, 'Subscription not found');
        }

        // Verify user owns this subscription
        if ($subscription->student_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if subscription is in failed state
        if ($subscription->payment_status !== SubscriptionPaymentStatus::FAILED) {
            return redirect()->route('student.subscriptions')
                ->with('info', 'هذا الاشتراك لا يحتاج إلى دفع يدوي.');
        }

        // Check grace period
        $metadata = $subscription->metadata ?? [];
        $gracePeriodExpiresAt = isset($metadata['grace_period_expires_at'])
            ? Carbon::parse($metadata['grace_period_expires_at'])
            : null;

        if (!$gracePeriodExpiresAt) {
            return redirect()->route('student.subscriptions')
                ->with('warning', 'لم يتم العثور على فترة سماح لهذا الاشتراك.');
        }

        if ($gracePeriodExpiresAt->isPast()) {
            return view('subscriptions.expired-grace-period', [
                'subscription' => $subscription,
                'expiredAt' => $gracePeriodExpiresAt,
            ]);
        }

        // Calculate renewal amount
        $renewalAmount = $subscription->calculateRenewalPrice();

        return view('subscriptions.manual-renewal', [
            'subscription' => $subscription,
            'renewalAmount' => $renewalAmount,
            'gracePeriodExpiresAt' => $gracePeriodExpiresAt,
            'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
        ]);
    }

    /**
     * Process manual renewal payment
     */
    public function process(Request $request, string $type, int $id)
    {
        $request->validate([
            'payment_gateway' => 'required|in:paymob,easykash',
        ]);

        $subscription = $this->findSubscription($type, $id);

        if (!$subscription) {
            abort(404, 'Subscription not found');
        }

        // Verify user owns this subscription
        if ($subscription->student_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Verify grace period not expired
        $metadata = $subscription->metadata ?? [];
        $gracePeriodExpiresAt = isset($metadata['grace_period_expires_at'])
            ? Carbon::parse($metadata['grace_period_expires_at'])
            : null;

        if (!$gracePeriodExpiresAt || $gracePeriodExpiresAt->isPast()) {
            return redirect()->route('student.subscriptions')
                ->with('error', 'انتهت فترة السماح لهذا الاشتراك.');
        }

        try {
            $renewalAmount = $subscription->calculateRenewalPrice();

            // Create payment record
            $payment = Payment::create([
                'academy_id' => $subscription->academy_id,
                'user_id' => Auth::id(),
                'subscription_id' => $subscription->id,
                'payment_code' => 'MRN-' . $subscription->id . '-' . now()->timestamp,
                'payment_method' => 'manual_renewal',
                'payment_gateway' => $request->payment_gateway,
                'payment_type' => ($type === 'quran' ? 'quran' : 'academic') . '_subscription_renewal',
                'amount' => $renewalAmount,
                'net_amount' => $renewalAmount,
                'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
                'status' => PaymentStatus::PENDING,
                'notes' => 'تجديد يدوي خلال فترة السماح',
                'payable_type' => get_class($subscription),
                'payable_id' => $subscription->id,
            ]);

            // Process payment
            $result = $this->paymentService->processPayment($payment);

            if ($result['success'] ?? false) {
                // Clear grace period and failure metadata
                $metadata = $subscription->metadata ?? [];
                unset(
                    $metadata['grace_period_expires_at'],
                    $metadata['grace_period_started_at'],
                    $metadata['renewal_failed_count'],
                    $metadata['last_renewal_failure_at'],
                    $metadata['last_renewal_failure_reason']
                );

                // Extend subscription
                $this->renewalProcessor->manualRenewal($subscription, $renewalAmount);

                Log::info('Manual renewal successful during grace period', [
                    'subscription_id' => $subscription->id,
                    'payment_id' => $payment->id,
                ]);

                return redirect()->route('student.subscriptions')
                    ->with('success', 'تم تجديد الاشتراك بنجاح!');
            } else {
                // Redirect to payment page if needed
                if (isset($result['data']['payment_url'])) {
                    return redirect($result['data']['payment_url']);
                }

                return back()->with('error', $result['error'] ?? 'فشل الدفع. يرجى المحاولة مرة أخرى.');
            }
        } catch (Exception $e) {
            Log::error('Manual renewal failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * Find subscription by type and ID
     */
    private function findSubscription(string $type, int $id)
    {
        return match ($type) {
            'quran' => QuranSubscription::find($id),
            'academic' => AcademicSubscription::find($id),
            default => null,
        };
    }
}
