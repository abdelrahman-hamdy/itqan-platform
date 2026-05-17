<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Services\PaymentService;
use Exception;
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

        // Get available gateways for this academy, filtered by the student's country.
        $gateways = $this->gatewayFactory->getAvailableGatewaysForAcademy($academy, Auth::user());

        // TEMP DEBUG: trace why Palestinian students still see Tap.
        // Remove after the live verification confirms the fix.
        try {
            $authUser = Auth::user();
            $sp = $authUser?->studentProfile;
            Log::info('quran.subscription.payment.create trace', [
                'subscription_id' => $subscription->id,
                'academy_id' => $academy->id,
                'auth_user_id' => $authUser?->id,
                'u_phone_country' => $authUser?->phone_country,
                'u_phone_country_code' => $authUser?->phone_country_code,
                'sp_id' => $sp?->id,
                'sp_phone_country' => $sp?->phone_country,
                'sp_phone_country_code' => $sp?->phone_country_code,
                'sp_nationality' => $sp?->nationality,
                'resolved_country' => app(\App\Services\Payment\UserCountryResolver::class)->resolve($authUser, $academy),
                'gateway_count' => count($gateways),
                'gateway_keys' => array_keys($gateways),
            ]);
        } catch (\Throwable $e) {
            Log::warning('quran.subscription.payment.create trace failed', ['error' => $e->getMessage()]);
        }

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

        // Server-side allowlist check. A stale browser tab or a direct POST
        // can submit a gateway that's no longer offered for this user's
        // resolved country (e.g. Tap for a Palestinian student after the
        // profile is corrected). Trust only the live filtered list, not the
        // value rendered into the page at an earlier point in time.
        $allowed = $this->gatewayFactory->getAvailableGatewaysForAcademy($academy, Auth::user());
        if (! array_key_exists($request->payment_gateway, $allowed)) {
            return redirect()
                ->route('quran.subscription.payment', [
                    'subdomain' => $academy->subdomain,
                    'subscription' => $subscription->id,
                ])
                ->with('error', __('payments.gateway_selection.no_gateways'));
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
            // `subscription.final_price` is stale when an early-renewal queued a new
            // cycle without syncing the subscription row (renewal queue branch);
            // resolve the cycle being paid for instead. Falls back for legacy subs
            // without cycles.
            //
            // When the subscription itself is still PENDING the user is paying off
            // their initial activation, NOT topping up a queued future cycle —
            // pendingPaymentCycle() prefers a queued cycle which would misroute
            // activation in the webhook (queued cycle paid, subscription stays
            // pending). Prefer currentCycle in that case, and tear down any
            // abandoned queued cycle that's blocking the path.
            if ($subscription->payment_status === SubscriptionPaymentStatus::PENDING) {
                $strayQueued = $subscription->queuedCycle()->first();
                if ($strayQueued !== null) {
                    $strayQueued->deleteIfAbandoned();
                    $subscription->refresh();
                }
                $pendingCycle = $subscription->currentCycle;
            } else {
                $pendingCycle = $subscription->pendingPaymentCycle();
            }
            $finalPrice = $pendingCycle?->final_price ?? $subscription->final_price;

            // Check for an existing pending payment with a valid gateway URL (idempotency)
            $existingPayment = Payment::forPayable(QuranSubscription::class, $subscription->id)
                ->where('status', PaymentStatus::PENDING)
                ->where('payment_gateway', $gateway)
                ->where('amount', $finalPrice)
                ->where('created_at', '>', now()->subHour())
                ->latest()
                ->first();

            if ($existingPayment) {
                $redirectUrl = $existingPayment->redirect_url ?? $existingPayment->iframe_url;
                if ($redirectUrl) {
                    return redirect()->away($redirectUrl);
                }
            }

            DB::beginTransaction();

            // Cancel any previous pending payments for this subscription.
            // Match polymorphically — `subscription_id` is null on cash
            // placeholders created by SubscriptionRenewalService, so a
            // subscription_id-only filter would leave them stranded as
            // pending forever.
            Payment::forPayable(QuranSubscription::class, $subscription->id)
                ->where('payment_type', 'subscription')
                ->where('status', PaymentStatus::PENDING->value)
                ->update(['status' => PaymentStatus::CANCELLED->value, 'payment_status' => 'cancelled']);

            // Create new payment record
            $payment = Payment::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'subscription_cycle_id' => $pendingCycle?->id,
                'payable_type' => QuranSubscription::class,
                'payable_id' => $subscription->id,
                'payment_code' => Payment::generatePaymentCode($academy->id, 'QSP'),
                'payment_method' => 'credit_card',
                'payment_gateway' => $gateway,
                'payment_type' => 'subscription',
                'amount' => $finalPrice,
                'net_amount' => $finalPrice,
                'currency' => getCurrencyCode(null, $academy),
                'tax_amount' => 0,
                'tax_percentage' => 0,
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

        } catch (Exception $e) {
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
     * Get the subscription that the current user is allowed to pay for.
     *
     * Two acceptable shapes:
     *   - `acceptsRetryPayment()` — classic "PENDING/PENDING" first-activation
     *     retry path. See `BaseSubscription::acceptsRetryPayment()` for why
     *     filtering on payment_status alone would resurrect cancelled subs
     *     (the zombie-routing bug).
     *   - `isCurrentCyclePaymentPending()` — hybrid "active sub, current
     *     cycle pending" shape. Lets the student finish paying the cycle
     *     they're already on. The query still scopes by academy + student,
     *     so cross-tenant / cross-student access is impossible.
     */
    private function getPendingSubscription(Academy $academy, $subscriptionId): ?QuranSubscription
    {
        $sub = QuranSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', Auth::id())
            ->first();

        if (! $sub) {
            return null;
        }

        // G6: route through the canonical SubscriptionViewState (same fix as
        // AcademicSubscriptionPaymentController).
        return app(\App\Services\Subscription\SubscriptionPresentation::class)
            ->viewStateFor($sub)
            ->allowsPaymentRetry()
                ? $sub
                : null;
    }
}
