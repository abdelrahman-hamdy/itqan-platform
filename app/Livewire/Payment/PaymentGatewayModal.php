<?php

namespace App\Livewire\Payment;

use App\Models\Academy;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal component for selecting a payment gateway before checkout.
 *
 * Shows all available/configured gateways for the academy.
 * If only one gateway is available, auto-selects and submits without showing the modal.
 */
class PaymentGatewayModal extends Component
{
    public bool $showModal = false;

    public array $availableGateways = [];

    public ?string $selectedGateway = null;

    #[Locked]
    public int $academyId;

    /**
     * Open the gateway selection modal.
     *
     * If only one gateway is available, dispatches selection immediately.
     */
    #[On('openGatewaySelection')]
    public function open(): void
    {
        $this->loadGateways();

        if (count($this->availableGateways) === 0) {
            $this->dispatch('gatewaySelectionError', message: __('payments.gateway_selection.no_gateways'));

            return;
        }

        // Auto-proceed if only one gateway
        if (count($this->availableGateways) === 1) {
            $gateway = array_key_first($this->availableGateways);
            $this->dispatch('gatewaySelected', gateway: $gateway);

            return;
        }

        $this->selectedGateway = null;
        $this->showModal = true;
    }

    public function selectGateway(string $gateway): void
    {
        $this->selectedGateway = $gateway;
    }

    public function confirm(): void
    {
        if (! $this->selectedGateway) {
            return;
        }

        $this->showModal = false;
        $this->dispatch('gatewaySelected', gateway: $this->selectedGateway);
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->selectedGateway = null;
    }

    private function loadGateways(): void
    {
        $academy = Academy::find($this->academyId);

        if (! $academy) {
            $this->availableGateways = [];

            return;
        }

        $factory = app(AcademyPaymentGatewayFactory::class);
        $gateways = $factory->getAvailableGatewaysForAcademy($academy);

        $this->availableGateways = [];

        foreach ($gateways as $name => $gateway) {
            $this->availableGateways[$name] = [
                'name' => $name,
                'display_name' => $gateway->getDisplayName(),
                'methods' => $gateway->getSupportedMethods(),
                'icon' => $this->getGatewayIcon($name),
            ];
        }
    }

    private function getGatewayIcon(string $gateway): string
    {
        return match ($gateway) {
            'paymob' => 'ri-visa-line',
            'easykash' => 'ri-secure-payment-line',
            'stc_pay' => 'ri-smartphone-line',
            'tapay' => 'ri-bank-card-line',
            'moyasar' => 'ri-bank-card-2-line',
            default => 'ri-money-dollar-circle-line',
        };
    }

    public function render()
    {
        return view('livewire.payment.payment-gateway-modal');
    }
}
