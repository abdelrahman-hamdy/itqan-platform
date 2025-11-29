<?php

namespace App\Livewire;

use App\Enums\CertificateTemplateStyle;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class IssueCertificateModal extends Component
{
    public $showModal = false;
    public $subscriptionType; // 'quran', 'academic', 'interactive', 'group_quran'
    public $subscriptionId;
    public $subscription;

    // Group circle context
    public $circleId;
    public $circle;
    public $students = [];
    public $selectedStudents = [];
    public $selectAll = false;

    // Form fields
    public $achievementText = '';
    public $templateStyle = 'modern';
    public $previewMode = false;

    // Validation rules
    protected function rules()
    {
        $rules = [
            'achievementText' => 'required|string|min:10|max:1000',
            'templateStyle' => 'required|in:modern,classic,elegant',
        ];

        if ($this->subscriptionType === 'group_quran') {
            $rules['selectedStudents'] = 'required|array|min:1';
        }

        return $rules;
    }

    protected $messages = [
        'achievementText.required' => 'يرجى كتابة نص الإنجاز',
        'achievementText.min' => 'نص الإنجاز يجب أن يكون 10 أحرف على الأقل',
        'achievementText.max' => 'نص الإنجاز يجب ألا يتجاوز 1000 حرف',
        'selectedStudents.required' => 'يرجى اختيار طالب واحد على الأقل',
        'selectedStudents.min' => 'يرجى اختيار طالب واحد على الأقل',
    ];

    public function mount($subscriptionType = null, $subscriptionId = null, $circleId = null)
    {
        $this->subscriptionType = $subscriptionType;
        $this->subscriptionId = $subscriptionId;
        $this->circleId = $circleId;

        if ($subscriptionType && $subscriptionId) {
            $this->loadSubscription();
        }

        if ($subscriptionType === 'group_quran' && $circleId) {
            $this->loadCircle();
        }
    }

    #[On('openModal')]
    public function openModal($subscriptionType, $subscriptionId = null, $circleId = null)
    {
        $this->subscriptionType = $subscriptionType;
        $this->subscriptionId = $subscriptionId;
        $this->circleId = $circleId;
        $this->showModal = true;
        $this->previewMode = false;
        $this->achievementText = '';
        $this->templateStyle = 'modern';
        $this->selectedStudents = [];
        $this->selectAll = false;

        if ($subscriptionType === 'group_quran' && $circleId) {
            $this->loadCircle();
        } elseif ($subscriptionId) {
            $this->loadSubscription();
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->previewMode = false;
        $this->reset(['achievementText', 'templateStyle', 'selectedStudents', 'selectAll']);
        $this->resetValidation();
    }

    protected function loadCircle()
    {
        $this->circle = QuranCircle::with(['academy'])->findOrFail($this->circleId);

        // Get all students with their certificate count for this circle
        $this->students = $this->circle->students()
            ->get()
            ->map(function($student) {
                // Count certificates issued to this student for this circle
                $certificateCount = \App\Models\Certificate::where('student_id', $student->id)
                    ->where('certificateable_type', QuranCircle::class)
                    ->where('certificateable_id', $this->circleId)
                    ->count();

                return [
                    'id' => $student->id,
                    'subscription_id' => (string) $student->id, // Cast to string for checkbox compatibility
                    'name' => $student->name ?? 'طالب',
                    'email' => $student->email ?? '',
                    'certificate_count' => $certificateCount,
                ];
            })->values()->toArray();

        // Check authorization
        $user = Auth::user();
        if (!$user->hasRole(['super_admin', 'admin', 'quran_teacher'])) {
            abort(403, 'غير مصرح لك بإصدار شهادات');
        }
    }

    protected function loadSubscription()
    {
        if ($this->subscriptionType === 'quran') {
            $this->subscription = QuranSubscription::with(['student', 'quranTeacher.user', 'academy'])
                ->findOrFail($this->subscriptionId);
        } elseif ($this->subscriptionType === 'academic') {
            $this->subscription = AcademicSubscription::with(['student', 'academicTeacher.user', 'academy'])
                ->findOrFail($this->subscriptionId);
        } elseif ($this->subscriptionType === 'interactive') {
            $this->subscription = InteractiveCourseEnrollment::with(['student.user', 'course.academy', 'course.assignedTeacher'])
                ->findOrFail($this->subscriptionId);
        }

        // Check authorization
        $user = Auth::user();
        if (!$user->hasRole(['super_admin', 'admin', 'quran_teacher', 'academic_teacher'])) {
            abort(403, 'غير مصرح لك بإصدار شهادات');
        }

        // Check if already has certificate
        if ($this->subscription && $this->subscription->certificate) {
            session()->flash('error', 'تم إصدار شهادة لهذا الطالب مسبقاً');
            $this->closeModal();
        }
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Cast all to string for checkbox value consistency
            $this->selectedStudents = collect($this->students)->pluck('subscription_id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function selectAllStudents()
    {
        $this->selectAll = true;
        // Cast all to string for checkbox value consistency
        $this->selectedStudents = collect($this->students)->pluck('subscription_id')->map(fn($id) => (string) $id)->toArray();
    }

    public function updatedSelectedStudents()
    {
        $this->selectAll = count($this->selectedStudents) === count($this->students);
    }

    public function setExampleText($text)
    {
        $this->achievementText = $text;
        // Dispatch browser event to ensure textarea updates
        $this->dispatch('achievement-text-updated', text: $text);
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
            $issuedCount = 0;

            if ($this->subscriptionType === 'group_quran') {
                // Issue certificates to selected students in group circle
                $errors = [];

                \Log::info('Starting group certificate issuance', [
                    'circle_id' => $this->circleId,
                    'selected_students' => $this->selectedStudents,
                    'achievement_text' => $this->achievementText,
                ]);

                foreach ($this->selectedStudents as $studentId) {
                    $student = User::find($studentId);
                    if ($student) {
                        try {
                            $certificateService->issueGroupCircleCertificate(
                                $this->circle,
                                $student,
                                $this->achievementText,
                                CertificateTemplateStyle::from($this->templateStyle),
                                Auth::id()
                            );
                            $issuedCount++;
                            \Log::info('Certificate issued successfully', ['student_id' => $studentId]);
                        } catch (\Exception $e) {
                            \Log::error('Certificate issuance failed', [
                                'student_id' => $studentId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            $errors[] = ($student->name ?? 'طالب') . ': ' . $e->getMessage();
                        }
                    } else {
                        \Log::warning('Student not found', ['student_id' => $studentId]);
                    }
                }

                if ($issuedCount > 0) {
                    session()->flash('success', "تم إصدار {$issuedCount} شهادة بنجاح!");
                    $this->showModal = false;
                    $this->reset(['achievementText', 'templateStyle', 'selectedStudents', 'selectAll', 'previewMode']);
                    // Dispatch event to trigger page reload
                    $this->dispatch('certificates-issued');
                    return;
                } elseif (!empty($errors)) {
                    session()->flash('error', 'فشل إصدار الشهادات: ' . implode('، ', array_slice($errors, 0, 3)));
                    return;
                } else {
                    session()->flash('error', 'لم يتم اختيار أي طالب');
                    return;
                }
            } else {
                // Single subscription certificate
                $teacherId = null;
                if ($this->subscriptionType === 'quran') {
                    $teacherId = $this->subscription->quran_teacher_id;
                } elseif ($this->subscriptionType === 'academic') {
                    $teacherId = $this->subscription->teacher_id;
                } elseif ($this->subscriptionType === 'interactive') {
                    $teacherId = $this->subscription->course->assigned_teacher_id;
                }

                $certificate = $certificateService->issueManualCertificate(
                    $this->subscription,
                    $this->achievementText,
                    CertificateTemplateStyle::from($this->templateStyle),
                    Auth::id(),
                    $teacherId
                );

                session()->flash('success', 'تم إصدار الشهادة بنجاح!');
                $this->showModal = false;
                $this->reset(['achievementText', 'templateStyle', 'selectedStudents', 'selectAll', 'previewMode']);
                $this->dispatch('certificate-issued', certificateId: $certificate->id);
                return;
            }

        } catch (\Exception $e) {
            \Log::error('Certificate issuance error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

    public function getIsGroupProperty()
    {
        return $this->subscriptionType === 'group_quran';
    }

    public function getStudentNameProperty()
    {
        if ($this->subscription) {
            if ($this->subscriptionType === 'interactive') {
                return $this->subscription->student->user->name ?? $this->subscription->student->name ?? 'طالب';
            }
            return $this->subscription->student->name ?? 'طالب';
        }
        return 'طالب';
    }

    public function getAcademyNameProperty()
    {
        if ($this->subscription) {
            if ($this->subscriptionType === 'interactive') {
                return $this->subscription->course->academy->name ?? 'الأكاديمية';
            }
            return $this->subscription->academy->name ?? 'الأكاديمية';
        }
        if ($this->circle) {
            return $this->circle->academy->name ?? 'الأكاديمية';
        }
        return 'الأكاديمية';
    }

    public function getTeacherNameProperty()
    {
        if ($this->subscription) {
            if ($this->subscriptionType === 'quran') {
                return $this->subscription->quranTeacher->user->name ?? 'المعلم';
            } elseif ($this->subscriptionType === 'academic') {
                return $this->subscription->academicTeacher->user->name ?? 'المعلم';
            } elseif ($this->subscriptionType === 'interactive') {
                return $this->subscription->course->assignedTeacher->full_name ?? 'المعلم';
            }
        }
        if ($this->circle) {
            return $this->circle->teacher->name ?? 'المعلم';
        }
        return 'المعلم';
    }

    public function render()
    {
        return view('livewire.issue-certificate-modal', [
            'templateStyles' => $this->templateStyles,
            'isGroup' => $this->isGroup,
            'studentName' => $this->studentName,
            'academyName' => $this->academyName,
            'teacherName' => $this->teacherName,
        ]);
    }
}
