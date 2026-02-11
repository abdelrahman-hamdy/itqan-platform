<?php

namespace App\Livewire\Student;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SavedPaymentMethod;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AutoRenewalAlert extends Component
{
    public bool $showAlert = false;

    public int $subscriptionsAtRisk = 0;

    public function mount(): void
    {
        $this->checkAutoRenewalStatus();
    }

    /**
     * Check if user has auto-renew subscriptions without saved cards
     */
    public function checkAutoRenewalStatus(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            $this->showAlert = false;
            $this->subscriptionsAtRisk = 0;

            return;
        }

        // Check if user has any active subscriptions with auto-renew enabled
        $quranSubscriptions = QuranSubscription::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->count();

        $academicSubscriptions = AcademicSubscription::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->count();

        $totalAutoRenewSubscriptions = $quranSubscriptions + $academicSubscriptions;

        // Check if user has a valid saved Paymob card
        $hasSavedCard = SavedPaymentMethod::where('user_id', $userId)
            ->where('gateway', 'paymob')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        // Show alert if auto-renew is enabled but no saved card exists
        $this->showAlert = $totalAutoRenewSubscriptions > 0 && !$hasSavedCard;
        $this->subscriptionsAtRisk = $this->showAlert ? $totalAutoRenewSubscriptions : 0;
    }

    /**
     * Dismiss the alert (temporarily - will reappear on refresh)
     */
    public function dismiss(): void
    {
        $this->showAlert = false;
    }

    /**
     * Redirect to payment methods page to add card
     */
    public function addCard()
    {
        return $this->redirect(route('student.payments'));
    }

    public function render()
    {
        return view('livewire.student.auto-renewal-alert');
    }
}
