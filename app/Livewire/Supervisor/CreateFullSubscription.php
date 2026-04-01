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

    public int $totalSteps = 4;

    // Step 1
    public string $subscription_type = 'quran_individual';

    public ?int $student_id = null;

    public string $selectedStudentName = '';

    public string $selectedStudentEmail = '';

    public string $student_search = '';

    public array $searchResults = [];

    public ?int $teacher_id = null;

    public string $teacher_search = '';

    public ?int $quran_circle_id = null;

    // Step 2
    public ?int $package_id = null;

    public string $billing_cycle = 'monthly';

    public float $amount = 0;

    public float $discount = 0;

    // Step 3
    public string $payment_source = 'outside'; // 'outside' or 'inside'

    public string $payment_method = 'cash';

    public string $payment_reference = '';

    // Step 4
    public string $memorization_level = 'beginner';

    public string $specialization = 'memorization';

    public string $learning_goals = '';

    // Data lists
    public array $availableTeachers = [];

    public array $filteredTeachers = [];

    public array $availablePackages = [];

    public array $availableCircles = [];

    protected function rules(): array
    {
        return [
            'subscription_type' => 'required|in:quran_individual,quran_group,academic',
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('academy_id', auth()->user()->academy_id)],
            'teacher_id' => 'required|integer',
            'package_id' => 'required|integer',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->hasRole(['super_admin', 'admin']) || $user->supervisorProfile?->canManageSubscriptions())) {
            abort(403);
        }
        $this->loadTeachers();
        $this->filteredTeachers = $this->availableTeachers;
    }

    // ── Reactive updates ──

    public function updatedSubscriptionType(): void
    {
        $this->teacher_id = null;
        $this->teacher_search = '';
        $this->quran_circle_id = null;
        $this->package_id = null;
        $this->amount = 0;
        $this->discount = 0;
        $this->availableCircles = [];
        $this->availablePackages = [];
        $this->loadTeachers();
        $this->filteredTeachers = $this->availableTeachers;
    }

    public function updatedTeacherSearch(): void
    {
        $search = mb_strtolower(trim($this->teacher_search));
        $this->filteredTeachers = $search
            ? collect($this->availableTeachers)->filter(fn ($t) => str_contains(mb_strtolower($t['name']), $search))->values()->toArray()
            : $this->availableTeachers;
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

    public function updatedStudentSearch(): void
    {
        if (mb_strlen($this->student_search) >= 2) {
            $this->searchResults = User::where('academy_id', auth()->user()->academy_id)
                ->where('user_type', 'student')
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->student_search}%")->orWhere('email', 'like', "%{$this->student_search}%"))
                ->limit(10)
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => trim($u->first_name.' '.$u->last_name), 'email' => $u->email, 'user_id' => $u->id])
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

    public function selectTeacher(int $id): void
    {
        $this->teacher_id = $id;
        $this->teacher_search = '';
        $this->quran_circle_id = null;
        $this->loadPackages();
        if ($this->subscription_type === 'quran_group') {
            $this->loadCircles();
        }
    }

    public function clearTeacher(): void
    {
        $this->teacher_id = null;
        $this->teacher_search = '';
        $this->filteredTeachers = $this->availableTeachers;
        $this->quran_circle_id = null;
        $this->package_id = null;
        $this->amount = 0;
        $this->availablePackages = [];
        $this->availableCircles = [];
    }

    // ── Computed properties ──

    public function getMaxSessionsProperty(): int
    {
        if (! $this->package_id) {
            return 0;
        }
        $pkg = collect($this->availablePackages)->firstWhere('id', $this->package_id);

        return $pkg ? ($pkg['sessions_per_month'] ?? 8) * max(1, BillingCycle::from($this->billing_cycle)->months()) : 0;
    }

    public function getFinalPriceProperty(): float
    {
        return max(0, $this->amount - $this->discount);
    }

    // ── Navigation ──

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $rules = ['subscription_type' => 'required', 'student_id' => 'required|integer', 'teacher_id' => 'required|integer'];
            if ($this->subscription_type === 'quran_group') {
                $rules['quran_circle_id'] = 'required|integer';
            }
            $this->validate($rules);
        } elseif ($this->currentStep === 2) {
            $this->validate(['package_id' => 'required|integer', 'billing_cycle' => 'required', 'amount' => 'required|numeric|min:0']);
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
            // Convert profile ID → user ID for Quran subscriptions
            $teacherValueForService = $this->teacher_id;
            if (in_array($this->subscription_type, ['quran_individual', 'quran_group'])) {
                $profile = QuranTeacherProfile::find($this->teacher_id);
                $teacherValueForService = $profile?->user_id ?? $this->teacher_id;
            }

            $data = [
                'type' => $this->subscription_type,
                'academy_id' => auth()->user()->academy_id,
                'student_id' => $this->student_id,
                'teacher_id' => $teacherValueForService,
                'package_id' => $this->package_id,
                'billing_cycle' => $this->billing_cycle,
                'amount' => $this->finalPrice,
                'discount' => $this->discount,
                'payment_method' => $this->payment_source === 'outside' ? $this->payment_method : 'bank_transfer',
                'payment_reference' => $this->payment_reference,
                'memorization_level' => $this->memorization_level,
                'specialization' => $this->specialization,
                'learning_goals' => $this->learning_goals,
            ];

            // If inside platform, mark as pending (student pays via gateway)
            if ($this->payment_source === 'inside') {
                $data['create_as_pending'] = true;
            }

            // Group circle
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

    private function loadPackages(): void
    {
        $academyId = auth()->user()->academy_id;
        $isQuran = in_array($this->subscription_type, ['quran_individual', 'quran_group']);
        $model = $isQuran ? QuranPackage::class : AcademicPackage::class;

        $this->availablePackages = $model::where('academy_id', $academyId)->where('is_active', true)->get()->toArray();
    }

    private function loadCircles(): void
    {
        if (! $this->teacher_id) {
            $this->availableCircles = [];

            return;
        }

        // Get user_id from profile_id for circle query
        $profile = QuranTeacherProfile::find($this->teacher_id);
        $teacherUserId = $profile?->user_id;

        $this->availableCircles = $teacherUserId
            ? QuranCircle::where('academy_id', auth()->user()->academy_id)
                ->where('quran_teacher_id', $teacherUserId)
                ->where('is_active', true)
                ->get(['id', 'name', 'enrolled_students', 'max_students'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'spots' => $c->max_students - $c->enrolled_students])
                ->toArray()
            : [];
    }

    private function calculateAmount(): void
    {
        if (! $this->package_id) {
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
