<?php

namespace App\Livewire\Payment;

use App\Models\SavedPaymentMethod;
use App\Services\Payment\PaymentMethodService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Livewire component for displaying and managing saved payment methods.
 *
 * Used on the student/parent payments page to show saved cards
 * and allow adding/removing payment methods.
 */
class SavedPaymentMethodsList extends Component
{
    public bool $showAddModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteMethodId = null;

    public bool $isDeleting = false;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    /**
     * Get the current user's saved payment methods.
     */
    #[Computed]
    public function paymentMethods()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return SavedPaymentMethod::where('user_id', $user->id)
            ->active()
            ->notExpired()
            ->orderByDesc('is_default')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check if user has any saved payment methods.
     */
    #[Computed]
    public function hasPaymentMethods(): bool
    {
        return $this->paymentMethods->isNotEmpty();
    }

    /**
     * Open the add payment method modal.
     */
    public function openAddModal(): void
    {
        $this->showAddModal = true;
        $this->clearMessages();
    }

    /**
     * Close the add payment method modal.
     */
    public function closeAddModal(): void
    {
        $this->showAddModal = false;
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(int $methodId): void
    {
        $this->deleteMethodId = $methodId;
        $this->showDeleteModal = true;
        $this->clearMessages();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function cancelDelete(): void
    {
        $this->deleteMethodId = null;
        $this->showDeleteModal = false;
    }

    /**
     * Delete a saved payment method.
     */
    public function deletePaymentMethod(): void
    {
        if (! $this->deleteMethodId) {
            return;
        }

        $this->isDeleting = true;
        $this->clearMessages();

        try {
            $user = Auth::user();
            $paymentMethod = SavedPaymentMethod::where('id', $this->deleteMethodId)
                ->where('user_id', $user->id)
                ->first();

            if (! $paymentMethod) {
                $this->errorMessage = 'طريقة الدفع غير موجودة';
                $this->cancelDelete();

                return;
            }

            $paymentMethodService = app(PaymentMethodService::class);
            $paymentMethodService->deletePaymentMethod($paymentMethod);

            $this->successMessage = 'تم حذف طريقة الدفع بنجاح';
            $this->cancelDelete();

            // Refresh the list
            unset($this->paymentMethods);

            $this->dispatch('payment-method-deleted');

        } catch (\Exception $e) {
            Log::error('Failed to delete payment method', [
                'method_id' => $this->deleteMethodId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'حدث خطأ أثناء حذف طريقة الدفع';
        } finally {
            $this->isDeleting = false;
        }
    }

    /**
     * Set a payment method as default.
     */
    public function setAsDefault(int $methodId): void
    {
        $this->clearMessages();

        try {
            $user = Auth::user();
            $paymentMethod = SavedPaymentMethod::where('id', $methodId)
                ->where('user_id', $user->id)
                ->first();

            if (! $paymentMethod) {
                $this->errorMessage = 'طريقة الدفع غير موجودة';

                return;
            }

            $paymentMethodService = app(PaymentMethodService::class);
            $paymentMethodService->setDefaultPaymentMethod($user, $paymentMethod);

            $this->successMessage = 'تم تعيين طريقة الدفع الافتراضية';

            // Refresh the list
            unset($this->paymentMethods);

            $this->dispatch('payment-method-updated');

        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', [
                'method_id' => $methodId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'حدث خطأ أثناء تحديث طريقة الدفع';
        }
    }

    /**
     * Handle successful payment method addition.
     */
    #[On('payment-method-added')]
    public function handlePaymentMethodAdded(): void
    {
        $this->closeAddModal();
        $this->successMessage = 'تم إضافة طريقة الدفع بنجاح';

        // Refresh the list
        unset($this->paymentMethods);
    }

    /**
     * Clear success and error messages.
     */
    protected function clearMessages(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function render()
    {
        return view('livewire.payment.saved-payment-methods-list');
    }
}
