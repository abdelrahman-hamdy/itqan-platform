<?php

namespace App\Livewire\Payment;

use Exception;
use App\Constants\DefaultAcademy;
use App\Models\Academy;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Component for adding a new payment method.
 *
 * Redirects user to Paymob's checkout page for card tokenization.
 * After successful tokenization, user is redirected back to payments page.
 */
class AddPaymentMethodModal extends Component
{
    public bool $isLoading = false;

    public ?string $errorMessage = null;

    public string $gateway = 'paymob';

    /**
     * Initiate the add card flow - redirect to Paymob.
     */
    #[On('openAddPaymentMethodModal')]
    public function initiateAddCard(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $user = Auth::user();
            $academy = $user->academy ?? Academy::where('subdomain', DefaultAcademy::subdomain())->first();

            if (! $academy) {
                throw new Exception('Academy not found');
            }

            // Get gateway factory
            $gatewayFactory = app(AcademyPaymentGatewayFactory::class);
            $gateway = $gatewayFactory->getGateway($academy, $this->gateway);

            // Check if gateway supports tokenization
            if (! method_exists($gateway, 'getTokenizationIframeUrl')) {
                $this->errorMessage = __('student.saved_payment_methods.add_card_info_message');
                $this->isLoading = false;

                return;
            }

            // Build callback URL
            $callbackUrl = route('payments.tokenization.callback', [
                'subdomain' => $academy->subdomain ?? DefaultAcademy::subdomain(),
            ]);

            // Get tokenization URL
            $result = $gateway->getTokenizationIframeUrl($user->id, [
                'academy_id' => $academy->id,
                'email' => $user->email,
                'first_name' => $user->first_name ?? $user->name,
                'last_name' => $user->last_name ?? '',
                'phone' => $user->phone ?? '',
                'callback_url' => $callbackUrl,
            ]);

            if (isset($result['iframe_url'])) {
                // Redirect to Paymob checkout page (full page redirect)
                $this->redirect($result['iframe_url']);
            } else {
                $this->errorMessage = $result['error'] ?? __('student.saved_payment_methods.load_form_error');
                $this->isLoading = false;
            }

        } catch (Exception $e) {
            Log::error('Failed to initiate add card', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = __('student.saved_payment_methods.load_form_error');
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.payment.add-payment-method-modal');
    }
}
