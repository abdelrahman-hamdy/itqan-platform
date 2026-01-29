<?php

namespace App\Livewire\Payment;

use App\Models\Academy;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal component for adding a new payment method.
 *
 * Displays the Paymob card tokenization iframe for secure card entry.
 * On successful tokenization, the card is saved and the modal closes.
 */
class AddPaymentMethodModal extends Component
{
    public bool $show = false;

    public bool $isLoading = true;

    public ?string $iframeUrl = null;

    public ?string $clientSecret = null;

    public ?string $errorMessage = null;

    public string $gateway = 'paymob';

    protected $listeners = [
        'closeAddPaymentMethodModal' => 'close',
    ];

    public function mount(bool $show = false): void
    {
        $this->show = $show;

        if ($show) {
            $this->loadTokenizationFrame();
        }
    }

    /**
     * Open the modal and load the tokenization frame.
     */
    #[On('openAddPaymentMethodModal')]
    public function open(): void
    {
        $this->show = true;
        $this->loadTokenizationFrame();
    }

    /**
     * Close the modal and reset state.
     */
    public function close(): void
    {
        $this->show = false;
        $this->reset(['iframeUrl', 'clientSecret', 'errorMessage', 'isLoading']);
        $this->dispatch('add-payment-method-modal-closed');
    }

    /**
     * Load the tokenization iframe from Paymob.
     */
    protected function loadTokenizationFrame(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $user = Auth::user();
            $academy = $user->academy ?? Academy::where('subdomain', 'itqan-academy')->first();

            if (! $academy) {
                throw new \Exception('Academy not found');
            }

            // Get gateway factory
            $gatewayFactory = app(AcademyPaymentGatewayFactory::class);
            $gateway = $gatewayFactory->getGateway($academy, $this->gateway);

            // Check if gateway supports tokenization
            if (! method_exists($gateway, 'getTokenizationIframeUrl')) {
                // Fallback: Use regular payment iframe with save_card flag
                $this->errorMessage = 'لإضافة بطاقة جديدة، قم بإجراء عملية دفع مع تحديد خيار "حفظ البطاقة"';
                $this->isLoading = false;

                return;
            }

            // Get tokenization iframe URL
            $result = $gateway->getTokenizationIframeUrl($user->id, [
                'academy_id' => $academy->id,
                'callback_url' => route('payments.tokenization.callback', [
                    'subdomain' => $academy->subdomain,
                ]),
            ]);

            if (isset($result['iframe_url'])) {
                $this->iframeUrl = $result['iframe_url'];
                $this->clientSecret = $result['client_secret'] ?? null;
            } else {
                $this->errorMessage = $result['error'] ?? 'فشل في تحميل نموذج إضافة البطاقة';
            }

        } catch (\Exception $e) {
            Log::error('Failed to load tokenization frame', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'حدث خطأ أثناء تحميل نموذج إضافة البطاقة';
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Handle successful tokenization callback.
     */
    #[On('tokenization-success')]
    public function handleTokenizationSuccess(array $data): void
    {
        Log::info('Tokenization success received', ['data' => $data]);

        $this->dispatch('payment-method-added', $data);
        $this->close();
    }

    /**
     * Handle failed tokenization.
     */
    #[On('tokenization-failed')]
    public function handleTokenizationFailed(string $error): void
    {
        $this->errorMessage = $error;
    }

    /**
     * Retry loading the tokenization frame.
     */
    public function retry(): void
    {
        $this->loadTokenizationFrame();
    }

    public function render()
    {
        return view('livewire.payment.add-payment-method-modal');
    }
}
