<?php

namespace App\Livewire\Supervisor;

use App\Models\AcademicPackage;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranPackage;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\Subscription\AdminSubscriptionWizardService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateFullSubscription extends Component
{
    // Step tracking
    public int $currentStep = 1;

    public int $totalSteps = 4;

    // Step 1: Type & Student
    public string $subscription_type = 'quran_individual';

    public ?int $student_id = null;

    public string $student_search = '';

    public ?int $teacher_id = null;

    // Step 2: Package & Pricing
    public ?int $package_id = null;

    public string $billing_cycle = 'monthly';

    public float $amount = 0;

    public float $discount = 0;

    // Step 3: Payment
    public string $payment_method = 'manual';

    public string $payment_reference = '';

    public string $payment_notes = '';

    // Step 4: Initial Progress
    public int $consumed_sessions = 0;

    public string $memorization_level = 'beginner';

    public string $specialization = 'memorization';

    // Subject/Grade for academic
    public ?int $subject_id = null;

    public ?int $grade_level_id = null;

    // Computed data
    public array $availableTeachers = [];

    public array $availablePackages = [];

    public array $searchResults = [];

    protected function rules(): array
    {
        return [
            'subscription_type' => 'required|in:quran_individual,quran_group,academic',
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('academy_id', auth()->user()->academy_id)],
            'teacher_id' => 'required|integer',
            'package_id' => 'required|integer',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:manual,bank_transfer,cash,other',
        ];
    }

    public function mount(): void
    {
        // Authorization: admin, super_admin, or supervisor with manage_subscriptions
        $user = auth()->user();
        if (! $user || ! ($user->hasRole(['super_admin', 'admin']) || $user->supervisorProfile?->canManageSubscriptions())) {
            abort(403);
        }

        $this->loadTeachers();
    }

    public function updatedSubscriptionType(): void
    {
        $this->teacher_id = null;
        $this->package_id = null;
        $this->amount = 0;
        $this->loadTeachers();
        $this->availablePackages = [];
    }

    public function updatedTeacherId(): void
    {
        $this->loadPackages();
    }

    public function updatedPackageId(): void
    {
        $this->calculateAmount();
    }

    public function updatedBillingCycle(): void
    {
        $this->calculateAmount();
    }

    public function updatedStudentSearch(): void
    {
        if (strlen($this->student_search) >= 2) {
            $academyId = auth()->user()->academy_id;
            $this->searchResults = User::where('academy_id', $academyId)
                ->where('user_type', 'student')
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->student_search}%")
                        ->orWhere('email', 'like', "%{$this->student_search}%");
                })
                ->limit(10)
                ->get(['id', 'name', 'email'])
                ->toArray();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectStudent(int $id): void
    {
        $student = User::where('id', $id)
            ->where('academy_id', auth()->user()->academy_id)
            ->first();
        if ($student) {
            $this->student_id = $student->id;
            $this->student_search = $student->name;
            $this->searchResults = [];
        }
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'subscription_type' => 'required',
                'student_id' => 'required|integer',
                'teacher_id' => 'required|integer',
            ]);
        } elseif ($this->currentStep === 2) {
            $this->validate([
                'package_id' => 'required|integer',
                'billing_cycle' => 'required',
                'amount' => 'required|numeric|min:0',
            ]);
        } elseif ($this->currentStep === 3) {
            $this->validate([
                'payment_method' => 'required',
            ]);
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function submit(): void
    {
        $this->validate();

        try {
            $subscription = app(AdminSubscriptionWizardService::class)->createFullSubscription([
                'type' => $this->subscription_type,
                'academy_id' => auth()->user()->academy_id,
                'student_id' => $this->student_id,
                'teacher_id' => $this->teacher_id,
                'package_id' => $this->package_id,
                'billing_cycle' => $this->billing_cycle,
                'amount' => $this->amount,
                'discount' => $this->discount,
                'payment_method' => $this->payment_method,
                'payment_reference' => $this->payment_reference,
                'payment_notes' => $this->payment_notes,
                'consumed_sessions' => $this->consumed_sessions,
                'memorization_level' => $this->memorization_level,
                'specialization' => $this->specialization,
                'subject_id' => $this->subject_id,
                'grade_level_id' => $this->grade_level_id,
            ]);

            $type = in_array($this->subscription_type, ['quran_individual', 'quran_group']) ? 'quran' : 'academic';
            $subdomain = auth()->user()->academy?->subdomain;

            session()->flash('success', __('subscriptions.create_full_subscription_success'));

            $this->redirect(route('manage.subscriptions.show', [
                'subdomain' => $subdomain,
                'type' => $type,
                'subscription' => $subscription->id,
            ]));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function loadTeachers(): void
    {
        $academyId = auth()->user()->academy_id;

        if (in_array($this->subscription_type, ['quran_individual', 'quran_group'])) {
            $this->availableTeachers = QuranTeacherProfile::where('academy_id', $academyId)
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->with('user')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user?->name ?? '-'])
                ->toArray();
        } else {
            $this->availableTeachers = AcademicTeacherProfile::where('academy_id', $academyId)
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->with('user')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user?->name ?? '-'])
                ->toArray();
        }
    }

    private function loadPackages(): void
    {
        $academyId = auth()->user()->academy_id;

        if (in_array($this->subscription_type, ['quran_individual', 'quran_group'])) {
            $this->availablePackages = QuranPackage::where('academy_id', $academyId)
                ->where('is_active', true)
                ->get()
                ->toArray();
        } else {
            $this->availablePackages = AcademicPackage::where('academy_id', $academyId)
                ->where('is_active', true)
                ->get()
                ->toArray();
        }
    }

    private function calculateAmount(): void
    {
        if (! $this->package_id) {
            return;
        }

        $package = collect($this->availablePackages)->firstWhere('id', $this->package_id);
        if (! $package) {
            return;
        }

        $this->amount = \App\Services\Subscription\PricingResolver::resolvePriceFromPackage(
            $package,
            \App\Enums\BillingCycle::from($this->billing_cycle),
            useSalePrices: false,
        );
    }

    public function render()
    {
        return view('supervisor.subscriptions.create')
            ->layout('components.layouts.supervisor');
    }
}
