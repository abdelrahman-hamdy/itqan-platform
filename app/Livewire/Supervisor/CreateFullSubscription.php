<?php

namespace App\Livewire\Supervisor;

use App\Enums\BillingCycle;
use App\Models\AcademicPackage;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranCircle;
use App\Models\QuranPackage;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\Subscription\AdminSubscriptionWizardService;
use App\Services\Subscription\PricingResolver;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateFullSubscription extends Component
{
    public int $currentStep = 1;

    // Step 1
    public string $subscription_type = 'quran_individual';

    public ?int $student_id = null;

    public string $selectedStudentName = '';

    public string $selectedStudentEmail = '';

    public string $student_search = '';

    public array $searchResults = [];

    public ?int $teacher_id = null;

    public ?int $quran_circle_id = null;

    // Step 2
    public ?int $package_id = null;

    public string $billing_cycle = 'monthly';

    public float $amount = 0;

    public float $discount = 0;

    // Step 3
    public string $payment_source = 'outside';

    public string $payment_reference = '';

    public bool $is_sponsored = false;

    // Step 4 (individual/academic only)
    public string $memorization_level = 'beginner';

    public string $specialization = 'memorization';

    public array $learning_goals = [];

    // Data lists
    public array $availableTeachers = [];

    public array $availablePackages = [];

    public array $availableCircles = [];

    public function getTotalStepsProperty(): int
    {
        // Group circles: 2 steps (type+student+circle, then sponsored/payment)
        // Individual Quran: 4 steps (type+student+teacher, package+pricing, payment, learning details)
        // Academic: 3 steps (type+student+teacher, package+pricing, payment)
        return match ($this->subscription_type) {
            'quran_group' => 2,
            'quran_individual' => 4,
            default => 3,
        };
    }

    protected function rules(): array
    {
        $rules = [
            'subscription_type' => 'required|in:quran_individual,quran_group,academic',
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('academy_id', auth()->user()->academy_id)],
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'amount' => 'required|numeric|min:0',
        ];

        if ($this->subscription_type === 'quran_group') {
            $rules['quran_circle_id'] = 'required|integer';
        } else {
            $rules['teacher_id'] = 'required|integer';
            $rules['package_id'] = 'required|integer';
        }

        return $rules;
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->hasRole(['super_admin', 'admin']) || $user->supervisorProfile?->canManageSubscriptions())) {
            abort(403);
        }
        $this->loadTeachers();
        $this->loadCircles();
        $this->loadPackages();
    }

    // ── Reactive updates ──

    public function updatedSubscriptionType(): void
    {
        $this->teacher_id = null;
        $this->quran_circle_id = null;
        $this->package_id = null;
        $this->amount = 0;
        $this->discount = 0;
        $this->is_sponsored = false;
        if ($this->subscription_type === 'quran_group') {
            $this->billing_cycle = 'monthly';
        }
        $this->loadTeachers();
        $this->loadCircles();
        $this->loadPackages();
    }

    public function updatedQuranCircleId(): void
    {
        if ($this->quran_circle_id) {
            $circle = collect($this->availableCircles)->firstWhere('id', $this->quran_circle_id);
            if ($circle) {
                $this->teacher_id = $circle['teacher_user_id'] ?? null;
                $this->amount = (float) ($circle['monthly_fee'] ?? 0);
            }
        }
    }

    public function updatedPackageId(): void
    {
        $this->calculateAmount();
        $this->clampDiscount();
    }

    public function updatedBillingCycle(): void
    {
        $this->calculateAmount();
        $this->clampDiscount();
    }

    public function updatedDiscount(): void
    {
        $this->clampDiscount();
    }

    public function updatedIsSponsored(): void
    {
        if ($this->is_sponsored) {
            $this->amount = 0;
            $this->discount = 0;
        } elseif ($this->subscription_type === 'quran_group') {
            $this->updatedQuranCircleId();
        } else {
            $this->calculateAmount();
        }
    }

    public function updatedStudentSearch(): void
    {
        if (mb_strlen($this->student_search) >= 2) {
            $this->searchResults = User::where('academy_id', auth()->user()->academy_id)
                ->where('user_type', 'student')
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->student_search}%")->orWhere('email', 'like', "%{$this->student_search}%"))
                ->limit(10)
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => trim($u->first_name.' '.$u->last_name), 'email' => $u->email])
                ->toArray();
        } else {
            $this->searchResults = [];
        }
    }

    // ── Selection actions ──

    public function selectStudent(int $id): void
    {
        $student = User::where('id', $id)->where('academy_id', auth()->user()->academy_id)->first();
        if ($student) {
            $this->student_id = $student->id;
            $this->selectedStudentName = trim($student->first_name.' '.$student->last_name);
            $this->selectedStudentEmail = $student->email ?? '';
            $this->student_search = '';
            $this->searchResults = [];
        }
    }

    public function clearStudent(): void
    {
        $this->student_id = null;
        $this->selectedStudentName = '';
        $this->selectedStudentEmail = '';
        $this->student_search = '';
    }

    // ── Computed properties ──

    public function getStepLabelsProperty(): array
    {
        if ($this->subscription_type === 'quran_group') {
            return [
                1 => __('subscriptions.wizard_step1_title'),
                2 => __('subscriptions.wizard_step3_title'),
            ];
        }

        $labels = [
            1 => __('subscriptions.wizard_step1_title'),
            2 => __('subscriptions.wizard_step2_title'),
            3 => __('subscriptions.wizard_step3_title'),
        ];

        if ($this->subscription_type === 'quran_individual') {
            $labels[4] = __('subscriptions.wizard_step4_title');
        }

        return $labels;
    }

    public function getFinalPriceProperty(): float
    {
        return max(0, $this->amount - $this->discount);
    }

    // ── Navigation ──

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $rules = ['subscription_type' => 'required', 'student_id' => 'required|integer'];
            if ($this->subscription_type === 'quran_group') {
                $rules['quran_circle_id'] = 'required|integer';
            } else {
                $rules['teacher_id'] = 'required|integer';
            }
            $this->validate($rules);
        } elseif ($this->currentStep === 2) {
            if ($this->subscription_type !== 'quran_group') {
                $this->validate(['package_id' => 'required|integer']);
            }
            $this->validate(['billing_cycle' => 'required', 'amount' => 'required|numeric|min:0']);
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
            // Convert profile ID → user ID for Quran individual/academic
            $teacherValueForService = $this->teacher_id;
            if ($this->subscription_type === 'quran_individual') {
                $profile = QuranTeacherProfile::find($this->teacher_id);
                $teacherValueForService = $profile?->user_id ?? $this->teacher_id;
            }

            // For group circles, teacher comes from circle
            if ($this->subscription_type === 'quran_group' && $this->quran_circle_id) {
                $circle = QuranCircle::find($this->quran_circle_id);
                $teacherValueForService = $circle?->quran_teacher_id;
            }

            $data = [
                'type' => $this->subscription_type,
                'academy_id' => auth()->user()->academy_id,
                'student_id' => $this->student_id,
                'teacher_id' => $teacherValueForService,
                'package_id' => $this->package_id,
                'billing_cycle' => $this->billing_cycle,
                'amount' => $this->is_sponsored ? 0 : $this->finalPrice,
                'discount' => $this->discount,
                'payment_method' => 'bank_transfer',
                'payment_reference' => $this->payment_reference,
                'memorization_level' => $this->memorization_level,
                'specialization' => $this->specialization,
                'learning_goals' => $this->learning_goals,
                'is_sponsored' => $this->is_sponsored,
            ];

            if ($this->is_sponsored) {
                // Sponsored = free, active immediately
                $data['create_as_pending'] = false;
            } elseif ($this->payment_source === 'inside') {
                $data['create_as_pending'] = true;
            }

            if ($this->subscription_type === 'quran_group' && $this->quran_circle_id) {
                $data['quran_circle_id'] = $this->quran_circle_id;
            }

            $subscription = app(AdminSubscriptionWizardService::class)->createFullSubscription($data);

            $type = in_array($this->subscription_type, ['quran_individual', 'quran_group']) ? 'quran' : 'academic';
            session()->flash('success', __('subscriptions.create_full_subscription_success'));
            $this->redirect(route('manage.subscriptions.show', [
                'subdomain' => auth()->user()->academy?->subdomain,
                'type' => $type,
                'subscription' => $subscription->id,
            ]));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ── Data loaders ──

    private function loadTeachers(): void
    {
        $academyId = auth()->user()->academy_id;
        $isQuran = in_array($this->subscription_type, ['quran_individual', 'quran_group']);
        $model = $isQuran ? QuranTeacherProfile::class : AcademicTeacherProfile::class;

        $this->availableTeachers = $model::where('academy_id', $academyId)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with('user')
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => trim(($t->user?->first_name ?? '').' '.($t->user?->last_name ?? '')), 'user_id' => $t->user_id])
            ->toArray();
    }

    private function loadCircles(): void
    {
        if ($this->subscription_type !== 'quran_group') {
            $this->availableCircles = [];

            return;
        }

        $this->availableCircles = QuranCircle::where('academy_id', auth()->user()->academy_id)
            ->where('status', true)
            ->with('quranTeacher')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'teacher_name' => $c->quranTeacher?->name ?? '-',
                'teacher_user_id' => $c->quran_teacher_id,
                'monthly_fee' => (float) $c->monthly_fee,
                'enrolled' => $c->enrolled_students,
                'max' => $c->max_students,
                'spots' => $c->max_students - $c->enrolled_students,
            ])
            ->toArray();
    }

    private function loadPackages(): void
    {
        $academyId = auth()->user()->academy_id;
        $isQuran = in_array($this->subscription_type, ['quran_individual', 'quran_group']);
        $model = $isQuran ? QuranPackage::class : AcademicPackage::class;

        $this->availablePackages = $model::where('academy_id', $academyId)->where('is_active', true)->get()->toArray();
    }

    private function calculateAmount(): void
    {
        if (! $this->package_id || $this->is_sponsored) {
            return;
        }
        $pkg = collect($this->availablePackages)->firstWhere('id', $this->package_id);
        if ($pkg) {
            $this->amount = PricingResolver::resolvePriceFromPackage($pkg, BillingCycle::from($this->billing_cycle), useSalePrices: false);
        }
    }

    private function clampDiscount(): void
    {
        $this->discount = max(0, min($this->discount, $this->amount));
    }

    public function render()
    {
        return view('supervisor.subscriptions.create')->layout('components.layouts.supervisor');
    }
}
