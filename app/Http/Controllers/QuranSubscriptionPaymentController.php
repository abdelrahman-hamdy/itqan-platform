<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Handles "Pay Now" for pending quran subscriptions.
 *
 * Flow: gateway selection (modal or auto) → create payment → redirect to gateway.
 * No custom card form - the gateway handles card collection.
 */
class QuranSubscriptionPaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private AcademyPaymentGatewayFactory $gatewayFactory,
    ) {}

    /**
     * Show gateway selection or auto-redirect if only one gateway.
     */
    public function create(Request $request, $subdomain, $subscriptionId): View|RedirectResponse
    {
        $academy = $request->academy ?? Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        if (! Auth::check() || Auth::user()->user_type !== UserType::STUDENT->value) {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.quran_payment.login_required', [], 'ar'));
        }

        $subscription = $this->getPendingSubscription($academy, $subscriptionId);

        if (! $subscription) {
            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.academic_payment.not_found'));
        }

        // Get available gateways for this academy
        $gateways = $this->gatewayFactory->getAvailableGatewaysForAcademy($academy);

        if (count($gateways) === 0) {
            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.gateway_selection.no_gateways'));
        }

        // Only one gateway → skip selection, process directly
        if (count($gateways) === 1) {
            return $this->processAndRedirect($academy, $subscription, array_key_first($gateways));
        }

        // Multiple gateways → show minimal page with gateway selection modal
        return view('payments.quran-subscription', compact('academy', 'subscription'));
    }

    /**
     * Process payment with selected gateway and redirect.
     */
    public function store(Request $request, $subdomain, $subscriptionId): RedirectResponse
    {
        $academy = $request->academy ?? Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        if (! Auth::check() || Auth::user()->user_type !== UserType::STUDENT->value) {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.quran_payment.login_required', [], 'ar'));
        }

        $request->validate([
            'payment_gateway' => 'required|string',
        ]);

        $subscription = $this->getPendingSubscription($academy, $subscriptionId);

        if (! $subscription) {
            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.academic_payment.not_found'));
        }

        return $this->processAndRedirect($academy, $subscription, $request->payment_gateway);
    }

    /**
     * Create payment record and redirect to gateway.
     */
    private function processAndRedirect(Academy $academy, QuranSubscription $subscription, string $gateway): RedirectResponse
    {
        $user = Auth::user();

        try {
            $finalPrice = $subscription->final_price;
            $taxAmount = round($finalPrice * 0.15, 2);
            $totalAmount = $finalPrice + $taxAmount;

            DB::beginTransaction();

            // Cancel any previous pending payments for this subscription
            Payment::where('subscription_id', $subscription->id)
                ->where('payment_type', 'subscription')
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);

            // Create new payment record
            $payment = Payment::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'payable_type' => \App\Models\QuranSubscription::class,
                'payable_id' => $subscription->id,
                'payment_code' => Payment::generatePaymentCode($academy->id, 'QSP'),
                'payment_method' => $gateway,
                'payment_gateway' => $gateway,
                'payment_type' => 'subscription',
                'amount' => $totalAmount,
                'net_amount' => $finalPrice,
                'currency' => getCurrencyCode(null, $academy),
                'tax_amount' => $taxAmount,
                'tax_percentage' => 15,
                'status' => 'pending',
                'payment_status' => 'pending',
                'created_by' => $user->id,
            ]);

            DB::commit();

            // Process payment with gateway - get redirect URL
            $studentProfile = $user->studentProfile;
            $result = $this->paymentService->processPayment($payment, [
                'customer_name' => $studentProfile->full_name ?? $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $studentProfile->phone ?? $user->phone ?? '',
            ]);

            // Redirect to gateway
            if (! empty($result['redirect_url'])) {
                return redirect()->away($result['redirect_url']);
            }

            if (! empty($result['iframe_url'])) {
                return redirect()->away($result['iframe_url']);
            }

            // Payment failed immediately
            if (! ($result['success'] ?? false)) {
                $payment->update(['status' => 'failed', 'payment_status' => 'failed']);

                return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                    ->with('error', __('payments.subscription.payment_init_failed').': '.($result['error'] ?? __('payments.subscription.unknown_error')));
            }

            // Fallback
            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Quran subscription payment failed', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.academic_payment.error'));
        }
    }

    /**
     * Get the pending subscription for the current user.
     */
    private function getPendingSubscription(Academy $academy, $subscriptionId): ?QuranSubscription
    {
        return QuranSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', Auth::id())
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->first();
    }
}
