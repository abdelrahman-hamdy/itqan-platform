<?php

namespace App\Livewire;

use Exception;
use App\Constants\DefaultAcademy;
use App\Enums\BillingCycle;
use App\Models\QuranPackage;
use App\Models\QuranTrialRequest;
use App\Services\TrialConversionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal component for converting completed trials to subscriptions
 *
 * This modal is shown to students after completing a trial session,
 * allowing them to subscribe with the same teacher.
 *
 * @property array|null $selectedPackage Computed property for selected package
 * @property float|null $selectedPrice Computed property for selected price
 * @property int|null $totalSessions Computed property for total sessions
 * @property array $billingCycleOptions Computed property for billing cycle options
 */
class TrialConversionModal extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?int $trialRequestId = null;

    public ?QuranTrialRequest $trialRequest = null;

    // Form fields
    public ?int $selectedPackageId = null;

    public string $selectedBillingCycle = 'monthly';

    // Packages data
    public array $packages = [];

    // State
    public bool $isProcessing = false;

    public ?string $errorMessage = null;

    public bool $showSuccess = false;

    public ?int $createdSubscriptionId = null;

    protected function rules(): array
    {
        return [
            'selectedPackageId' => 'required|exists:quran_packages,id',
            'selectedBillingCycle' => 'required|in:monthly,quarterly,yearly',
        ];
    }

    protected function messages(): array
    {
        return [
            'selectedPackageId.required' => __('student.trial_conversion.validation.package_required'),
            'selectedPackageId.exists' => __('student.trial_conversion.validation.package_invalid'),
            'selectedBillingCycle.required' => __('student.trial_conversion.validation.billing_cycle_required'),
            'selectedBillingCycle.in' => __('student.trial_conversion.validation.billing_cycle_invalid'),
        ];
    }

    public function mount(?int $trialRequestId = null): void
    {
        if ($trialRequestId) {
            $this->loadTrialRequest($trialRequestId);
        }
    }

    #[On('openTrialConversionModal')]
    public function openModal(int $trialRequestId): void
    {
        $this->resetState();
        $this->loadTrialRequest($trialRequestId);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetState();
    }

    protected function resetState(): void
    {
        $this->trialRequestId = null;
        $this->trialRequest = null;
        $this->selectedPackageId = null;
        $this->selectedBillingCycle = 'monthly';
        $this->packages = [];
        $this->isProcessing = false;
        $this->errorMessage = null;
        $this->showSuccess = false;
        $this->createdSubscriptionId = null;
        $this->resetValidation();
    }

    protected function loadTrialRequest(int $trialRequestId): void
    {
        $this->trialRequestId = $trialRequestId;

        $this->trialRequest = QuranTrialRequest::with(['student', 'teacher.user', 'academy'])
            ->find($trialRequestId);

        if (! $this->trialRequest) {
            $this->errorMessage = __('student.trial_conversion.errors.not_found');

            return;
        }

        // Ensure the trial request belongs to the authenticated user
        if ($this->trialRequest->student_id !== Auth::id()) {
            $this->errorMessage = __('student.trial_conversion.errors.not_found');
            $this->trialRequest = null;

            return;
        }

        // Check eligibility
        $conversionService = app(TrialConversionService::class);
        if (! $conversionService->isEligibleForConversion($this->trialRequest)) {
            if ($conversionService->wasConverted($this->trialRequest)) {
                $this->errorMessage = __('student.trial_conversion.errors.already_converted');
            } else {
                $this->errorMessage = __('student.trial_conversion.errors.not_eligible');
            }

            return;
        }

        // Load available packages
        $this->packages = $conversionService->getAvailablePackages($this->trialRequest)
            ->map(function (QuranPackage $package) {
                return [
                    'id' => $package->id,
                    'name' => $package->getDisplayName(),
                    'description' => $package->description,
                    'sessions_per_month' => $package->sessions_per_month,
                    'session_duration' => $package->session_duration_minutes,
                    'monthly_price' => $package->monthly_price,
                    'quarterly_price' => $package->quarterly_price,
                    'yearly_price' => $package->yearly_price,
                    'features' => $package->features ?? [],
                    'currency' => $package->getDisplayCurrency(),
                ];
            })->toArray();

        // Pre-select first package if available
        if (count($this->packages) > 0) {
            $this->selectedPackageId = $this->packages[0]['id'];
        }
    }

    public function getSelectedPackageProperty(): ?array
    {
        if (! $this->selectedPackageId) {
            return null;
        }

        return collect($this->packages)->firstWhere('id', $this->selectedPackageId);
    }

    public function getSelectedPriceProperty(): ?float
    {
        $package = $this->selectedPackage;
        if (! $package) {
            return null;
        }

        return match ($this->selectedBillingCycle) {
            'quarterly' => $package['quarterly_price'],
            'yearly' => $package['yearly_price'],
            default => $package['monthly_price'],
        };
    }

    public function getTotalSessionsProperty(): ?int
    {
        $package = $this->selectedPackage;
        if (! $package) {
            return null;
        }

        $multiplier = match ($this->selectedBillingCycle) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };

        return $package['sessions_per_month'] * $multiplier;
    }

    public function getBillingCycleOptionsProperty(): array
    {
        return [
            'monthly' => [
                'label' => __('student.trial_conversion.billing_cycles.monthly_label'),
                'description' => __('student.trial_conversion.billing_cycles.monthly_description'),
            ],
            'quarterly' => [
                'label' => __('student.trial_conversion.billing_cycles.quarterly_label'),
                'description' => __('student.trial_conversion.billing_cycles.quarterly_description'),
            ],
            'yearly' => [
                'label' => __('student.trial_conversion.billing_cycles.yearly_label'),
                'description' => __('student.trial_conversion.billing_cycles.yearly_description'),
            ],
        ];
    }

    public function convert(): void
    {
        $this->validate();

        if (! $this->trialRequest) {
            $this->errorMessage = __('student.trial_conversion.errors.not_found');

            return;
        }

        // Re-verify the trial request still belongs to the authenticated user before converting
        $freshTrialRequest = QuranTrialRequest::where('id', $this->trialRequestId)
            ->where('student_id', Auth::id())
            ->first();

        if (! $freshTrialRequest) {
            $this->errorMessage = __('student.trial_conversion.errors.not_found');

            return;
        }

        $this->trialRequest = $freshTrialRequest;
        $this->isProcessing = true;
        $this->errorMessage = null;

        try {
            $conversionService = app(TrialConversionService::class);

            $package = QuranPackage::findOrFail($this->selectedPackageId);
            $billingCycle = BillingCycle::from($this->selectedBillingCycle);

            $subscription = $conversionService->convertToSubscription(
                $this->trialRequest,
                $package,
                $billingCycle,
                Auth::user()
            );

            $this->createdSubscriptionId = $subscription->id;
            $this->showSuccess = true;

            // Dispatch success event
            $this->dispatch('trial-converted-success', [
                'subscriptionId' => $subscription->id,
                'message' => __('student.trial_conversion.success.created'),
            ]);

        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();

            $this->dispatch('trial-converted-error', [
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function goToPayment(): void
    {
        if ($this->createdSubscriptionId) {
            // Redirect to payment page
            $subdomain = $this->trialRequest?->academy?->subdomain ?? DefaultAcademy::subdomain();

            $this->redirect(route('quran-subscription.payment', [
                'subdomain' => $subdomain,
                'subscription' => $this->createdSubscriptionId,
            ]));
        }
    }

    public function render()
    {
        return view('livewire.trial-conversion-modal', [
            'selectedPackage' => $this->selectedPackage,
            'selectedPrice' => $this->selectedPrice,
            'totalSessions' => $this->totalSessions,
            'billingCycleOptions' => $this->billingCycleOptions,
        ]);
    }
}
