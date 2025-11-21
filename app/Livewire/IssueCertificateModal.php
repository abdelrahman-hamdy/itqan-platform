<?php

namespace App\Livewire;

use App\Enums\CertificateTemplateStyle;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IssueCertificateModal extends Component
{
    public $showModal = false;
    public $subscriptionType; // 'quran' or 'academic'
    public $subscriptionId;
    public $subscription;

    // Form fields
    public $achievementText = '';
    public $templateStyle = 'modern';
    public $previewMode = false;

    // Validation rules
    protected $rules = [
        'achievementText' => 'required|string|min:10|max:1000',
        'templateStyle' => 'required|in:modern,classic,elegant',
    ];

    protected $messages = [
        'achievementText.required' => 'يرجى كتابة نص الإنجاز',
        'achievementText.min' => 'نص الإنجاز يجب أن يكون 10 أحرف على الأقل',
        'achievementText.max' => 'نص الإنجاز يجب ألا يتجاوز 1000 حرف',
    ];

    public function mount($subscriptionType = null, $subscriptionId = null)
    {
        $this->subscriptionType = $subscriptionType;
        $this->subscriptionId = $subscriptionId;

        if ($subscriptionType && $subscriptionId) {
            $this->loadSubscription();
        }
    }

    public function openModal($subscriptionType, $subscriptionId)
    {
        $this->subscriptionType = $subscriptionType;
        $this->subscriptionId = $subscriptionId;
        $this->loadSubscription();
        $this->showModal = true;
        $this->previewMode = false;
        $this->achievementText = '';
        $this->templateStyle = 'modern';
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->previewMode = false;
        $this->reset(['achievementText', 'templateStyle']);
        $this->resetValidation();
    }

    protected function loadSubscription()
    {
        if ($this->subscriptionType === 'quran') {
            $this->subscription = QuranSubscription::with(['student', 'teacher', 'academy'])
                ->findOrFail($this->subscriptionId);
        } else {
            $this->subscription = AcademicSubscription::with(['student', 'teacher', 'academy'])
                ->findOrFail($this->subscriptionId);
        }

        // Check authorization
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'quran_teacher', 'academic_teacher'])) {
            abort(403, 'غير مصرح لك بإصدار شهادات');
        }

        // Check if already has certificate
        if ($this->subscription->certificate()->exists()) {
            session()->flash('error', 'تم إصدار شهادة لهذا الطالب مسبقاً');
            $this->closeModal();
        }
    }

    public function togglePreview()
    {
        $this->validate();
        $this->previewMode = !$this->previewMode;
    }

    public function issueCertificate()
    {
        $this->validate();

        try {
            $certificateService = app(CertificateService::class);

            $certificate = $certificateService->issueManualCertificate(
                $this->subscription,
                $this->achievementText,
                CertificateTemplateStyle::from($this->templateStyle),
                Auth::id(),
                $this->subscription->teacher_id
            );

            session()->flash('success', 'تم إصدار الشهادة بنجاح!');

            $this->dispatch('certificate-issued', certificateId: $certificate->id);

            $this->closeModal();

            // Redirect or refresh the page
            return redirect()->back();

        } catch (\Exception $e) {
            session()->flash('error', 'حدث خطأ أثناء إصدار الشهادة: ' . $e->getMessage());
        }
    }

    public function getTemplateStylesProperty()
    {
        return [
            'modern' => [
                'label' => 'عصري',
                'description' => 'تصميم حديث بألوان زرقاء وخضراء',
                'icon' => 'ri-contrast-2-line',
                'color' => 'blue',
            ],
            'classic' => [
                'label' => 'كلاسيكي',
                'description' => 'تصميم تقليدي رسمي',
                'icon' => 'ri-layout-line',
                'color' => 'gray',
            ],
            'elegant' => [
                'label' => 'أنيق',
                'description' => 'تصميم فاخر بألوان ذهبية',
                'icon' => 'ri-vip-crown-line',
                'color' => 'amber',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.issue-certificate-modal', [
            'templateStyles' => $this->templateStyles,
        ]);
    }
}
