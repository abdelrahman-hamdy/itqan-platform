<?php

namespace App\Livewire\Payment;

use Illuminate\Support\Collection;
use App\Models\SavedPaymentMethod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Payment method selector for checkout pages.
 *
 * Displays saved payment methods and allows the user to select one,
 * or choose to add a new card/use a different payment method.
 *
 * @property Collection $savedMethods
 * @property SavedPaymentMethod|null $defaultMethod
 */
class PaymentMethodSelector extends Component
{
    public ?int $selectedMethodId = null;

    public string $paymentType = 'saved'; // 'saved', 'new', 'wallet', 'apple_pay'

    public bool $saveCard = true;

    public bool $showAddModal = false;

    public string $gateway = 'paymob';

    /**
     * Get user's saved payment methods.
     */
    #[Computed]
    public function savedMethods()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return SavedPaymentMethod::where('user_id', $user->id)
            ->forGateway($this->gateway)
            ->active()
            ->notExpired()
            ->orderByDesc('is_default')
            ->orderByDesc('last_used_at')
            ->get();
    }

    /**
     * Check if user has saved payment methods.
     */
    #[Computed]
    public function hasSavedMethods(): bool
    {
        return $this->savedMethods->isNotEmpty();
    }

    /**
     * Get the default payment method.
     */
    #[Computed]
    public function defaultMethod()
    {
        return $this->savedMethods->firstWhere('is_default', true)
            ?? $this->savedMethods->first();
    }

    public function mount(?int $preselectedId = null): void
    {
        // Pre-select a payment method
        if ($preselectedId) {
            $this->selectedMethodId = $preselectedId;
            $this->paymentType = 'saved';
        } elseif ($this->defaultMethod) {
            $this->selectedMethodId = $this->defaultMethod->id;
            $this->paymentType = 'saved';
        } else {
            $this->paymentType = 'new';
        }
    }

    /**
     * Select a saved payment method.
     */
    public function selectMethod(int $methodId): void
    {
        $this->selectedMethodId = $methodId;
        $this->paymentType = 'saved';

        $this->dispatch('payment-method-selected', [
            'type' => 'saved',
            'method_id' => $methodId,
        ]);
    }

    /**
     * Switch to new card entry.
     */
    public function useNewCard(): void
    {
        $this->selectedMethodId = null;
        $this->paymentType = 'new';

        $this->dispatch('payment-method-selected', [
            'type' => 'new',
            'save_card' => $this->saveCard,
        ]);
    }

    /**
     * Switch to wallet payment.
     */
    public function useWallet(): void
    {
        $this->selectedMethodId = null;
        $this->paymentType = 'wallet';

        $this->dispatch('payment-method-selected', [
            'type' => 'wallet',
        ]);
    }

    /**
     * Toggle save card option.
     */
    public function toggleSaveCard(): void
    {
        $this->saveCard = ! $this->saveCard;

        if ($this->paymentType === 'new') {
            $this->dispatch('payment-method-selected', [
                'type' => 'new',
                'save_card' => $this->saveCard,
            ]);
        }
    }

    /**
     * Handle new card added event.
     */
    #[On('payment-method-added')]
    public function handleMethodAdded(array $data = []): void
    {
        $this->showAddModal = false;

        // Refresh saved methods
        unset($this->savedMethods);

        // Select the newly added card if we have its ID
        if (isset($data['method_id'])) {
            $this->selectMethod($data['method_id']);
        }
    }

    /**
     * Get selected payment data for form submission.
     */
    public function getSelectedPaymentData(): array
    {
        return [
            'type' => $this->paymentType,
            'saved_payment_method_id' => $this->paymentType === 'saved' ? $this->selectedMethodId : null,
            'save_card' => $this->paymentType === 'new' ? $this->saveCard : false,
        ];
    }

    public function render()
    {
        return view('livewire.payment.payment-method-selector');
    }
}
