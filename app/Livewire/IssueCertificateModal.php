<?php

namespace App\Livewire;

use App\Models\Certificate;
use Log;
use Exception;
use App\Enums\CertificateTemplateStyle;
use App\Enums\UserType;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property array $templateStyles
 * @property bool $isGroup
 * @property string $studentName
 * @property string $academyName
 * @property string $teacherName
 */
class IssueCertificateModal extends Component
{
    public $showModal = false;

    public $subscriptionType; // 'quran', 'academic', 'interactive', 'group_quran'

    public $subscriptionId;

    public $subscription;

    // Group circle context
    public $circleId;

    public $circle;

    // Interactive course context
    public $course;

    public $students = [];

    public $selectedStudents = [];

    public $selectAll = false;

    // Form fields
    public $achievementText = '';

    public $templateStyle = 'template_1';

    public $previewMode = false;

    // Validation rules
    protected function rules()
    {
        $rules = [
            'achievementText' => 'required|string|min:10|max:1000',
            'templateStyle' => 'required|string',
        ];

        if ($this->subscriptionType === 'group_quran' || $this->subscriptionType === 'interactive') {
            $rules['selectedStudents'] = 'required|array|min:1';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'achievementText.required' => __('components.certificate.modal.validation.achievement_required'),
            'achievementText.min' => __('components.certificate.modal.validation.achievement_min'),
            'achievementText.max' => __('components.certificate.modal.validation.achievement_max'),
            'selectedStudents.required' => __('components.certificate.modal.validation.students_required'),
            'selectedStudents.min' => __('components.certificate.modal.validation.students_required'),
        ];
    }

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
        $this->templateStyle = 'template_1';
        $this->selectedStudents = [];
        $this->selectAll = false;

        if ($subscriptionType === 'group_quran' && $circleId) {
            $this->loadCircle();
        } elseif ($subscriptionType === 'interactive' && $circleId) {
            $this->loadInteractiveCourse();
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
            ->map(function ($student) {
                // Count certificates issued to this student for this circle
                $certificateCount = Certificate::where('student_id', $student->id)
                    ->where('certificateable_type', QuranCircle::class)
                    ->where('certificateable_id', $this->circleId)
                    ->count();

                return [
                    'id' => $student->id,
                    'subscription_id' => (string) $student->id, // Cast to string for checkbox compatibility
                    'name' => $student->name ?? __('components.certificate.modal.fallbacks.student'),
                    'email' => $student->email ?? '',
                    'certificate_count' => $certificateCount,
                ];
            })->values()->toArray();

        // Check authorization
        $user = Auth::user();
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::QURAN_TEACHER->value])) {
            abort(403, __('components.certificate.modal.messages.unauthorized'));
        }
    }

    protected function loadInteractiveCourse()
    {
        $this->course = InteractiveCourse::with(['academy', 'enrollments.student'])->findOrFail($this->circleId);

        // Get all enrolled students with their certificate count for this course
        $this->students = $this->course->enrollments()
            ->with('student')
            ->get()
            ->map(function ($enrollment) {
                // Count certificates issued to this student for this course
                $certificateCount = Certificate::where('student_id', $enrollment->student_id)
                    ->where('certificateable_type', InteractiveCourse::class)
                    ->where('certificateable_id', $this->circleId)
                    ->count();

                return [
                    'id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'subscription_id' => (string) $enrollment->student_id, // Cast to string for checkbox compatibility
                    'name' => $enrollment->student->name ?? __('components.certificate.modal.fallbacks.student'),
                    'email' => $enrollment->student->email ?? '',
                    'certificate_count' => $certificateCount,
                ];
            })->values()->toArray();

        // Check authorization
        $user = Auth::user();
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::ACADEMIC_TEACHER->value])) {
            abort(403, __('components.certificate.modal.messages.unauthorized'));
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
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            abort(403, __('components.certificate.modal.messages.unauthorized'));
        }

        // Check if already has certificate
        if ($this->subscription && $this->subscription->certificate) {
            session()->flash('error', __('components.certificate.modal.messages.already_issued'));
            $this->closeModal();
        }
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Cast all to string for checkbox value consistency
            $this->selectedStudents = collect($this->students)->pluck('subscription_id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function selectAllStudents()
    {
        $this->selectAll = true;
        // Cast all to string for checkbox value consistency
        $this->selectedStudents = collect($this->students)->pluck('subscription_id')->map(fn ($id) => (string) $id)->toArray();
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
        $this->previewMode = ! $this->previewMode;
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

                Log::info('Starting group certificate issuance', [
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
                            Log::info('Certificate issued successfully', ['student_id' => $studentId]);
                        } catch (Exception $e) {
                            Log::error('Certificate issuance failed', [
                                'student_id' => $studentId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $errors[] = ($student->name ?? __('components.certificate.modal.fallbacks.student')).': '.$e->getMessage();
                        }
                    } else {
                        Log::warning('Student not found', ['student_id' => $studentId]);
                    }
                }

                if ($issuedCount > 0) {
                    // Close modal first
                    $this->showModal = false;
                    $this->reset(['achievementText', 'templateStyle', 'selectedStudents', 'selectAll', 'previewMode']);

                    // Set flash message for after reload
                    $successMessage = __('components.certificate.modal.messages.success_count', ['count' => $issuedCount]);
                    session()->flash('success', $successMessage);

                    // Dispatch success event with notification data
                    $this->dispatch('certificate-issued-success', [
                        'message' => $successMessage,
                        'count' => $issuedCount,
                    ]);

                    return;
                } elseif (! empty($errors)) {
                    $errorMessage = __('components.certificate.modal.messages.failed', ['errors' => implode('، ', array_slice($errors, 0, 3))]);
                    session()->flash('error', $errorMessage);
                    $this->dispatch('certificate-issued-error', [
                        'message' => $errorMessage,
                    ]);

                    return;
                } else {
                    session()->flash('error', __('components.certificate.modal.messages.no_students_selected'));

                    return;
                }
            } elseif ($this->subscriptionType === 'interactive') {
                // Issue certificates to selected students in interactive course
                $errors = [];

                Log::info('Starting interactive course certificate issuance', [
                    'course_id' => $this->circleId,
                    'selected_students' => $this->selectedStudents,
                    'achievement_text' => $this->achievementText,
                ]);

                foreach ($this->selectedStudents as $studentId) {
                    $student = User::find($studentId);
                    if ($student) {
                        try {
                            $certificateService->issueInteractiveCourseCertificate(
                                $this->course,
                                $student,
                                $this->achievementText,
                                CertificateTemplateStyle::from($this->templateStyle),
                                Auth::id()
                            );
                            $issuedCount++;
                            Log::info('Certificate issued successfully', ['student_id' => $studentId]);
                        } catch (Exception $e) {
                            Log::error('Certificate issuance failed', [
                                'student_id' => $studentId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $errors[] = ($student->name ?? __('components.certificate.modal.fallbacks.student')).': '.$e->getMessage();
                        }
                    } else {
                        Log::warning('Student not found', ['student_id' => $studentId]);
                    }
                }

                if ($issuedCount > 0) {
                    // Close modal first
                    $this->showModal = false;
                    $this->reset(['achievementText', 'templateStyle', 'selectedStudents', 'selectAll', 'previewMode']);

                    // Set flash message for after reload
                    $successMessage = __('components.certificate.modal.messages.success_count', ['count' => $issuedCount]);
                    session()->flash('success', $successMessage);

                    // Dispatch success event with notification data
                    $this->dispatch('certificate-issued-success', [
                        'message' => $successMessage,
                        'count' => $issuedCount,
                    ]);

                    return;
                } elseif (! empty($errors)) {
                    $errorMessage = __('components.certificate.modal.messages.failed', ['errors' => implode('، ', array_slice($errors, 0, 3))]);
                    session()->flash('error', $errorMessage);
                    $this->dispatch('certificate-issued-error', [
                        'message' => $errorMessage,
                    ]);

                    return;
                } else {
                    session()->flash('error', __('components.certificate.modal.messages.no_students_selected'));

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

                // Close modal first
                $this->showModal = false;
                $this->reset(['achievementText', 'templateStyle', 'selectedStudents', 'selectAll', 'previewMode']);

                // Set flash message for after reload
                $successMessage = __('components.certificate.modal.messages.success_single');
                session()->flash('success', $successMessage);

                // Dispatch success event with notification data
                $this->dispatch('certificate-issued-success', [
                    'message' => $successMessage,
                    'certificateId' => $certificate->id,
                ]);

                return;
            }

        } catch (Exception $e) {
            Log::error('Certificate issuance error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $errorMessage = __('components.certificate.modal.messages.error_occurred', ['error' => $e->getMessage()]);
            session()->flash('error', $errorMessage);
            $this->dispatch('certificate-issued-error', [
                'message' => $errorMessage,
            ]);
        }
    }

    public function getTemplateStylesProperty()
    {
        return collect(CertificateTemplateStyle::cases())->mapWithKeys(function ($style) {
            return [
                $style->value => [
                    'label' => $style->label(),
                    'description' => $style->description(),
                    'icon' => $style->icon(),
                    'color' => 'blue',
                    'previewImage' => $style->previewImageUrl(),
                ],
            ];
        })->toArray();
    }

    public function getIsGroupProperty()
    {
        return $this->subscriptionType === 'group_quran' || $this->subscriptionType === 'interactive';
    }

    public function getStudentNameProperty()
    {
        $fallback = __('components.certificate.modal.fallbacks.student');
        if ($this->subscription) {
            if ($this->subscriptionType === 'interactive') {
                return $this->subscription->student->user->name ?? $this->subscription->student->name ?? $fallback;
            }

            return $this->subscription->student->name ?? $fallback;
        }

        return $fallback;
    }

    public function getAcademyNameProperty()
    {
        $fallback = __('components.certificate.modal.fallbacks.academy');
        if ($this->subscription) {
            if ($this->subscriptionType === 'interactive') {
                return $this->subscription->course->academy->name ?? $fallback;
            }

            return $this->subscription->academy->name ?? $fallback;
        }
        if ($this->circle) {
            return $this->circle->academy->name ?? $fallback;
        }
        if ($this->course) {
            return $this->course->academy->name ?? $fallback;
        }

        return $fallback;
    }

    public function getTeacherNameProperty()
    {
        $fallback = __('components.certificate.modal.fallbacks.teacher');
        if ($this->subscription) {
            if ($this->subscriptionType === 'quran') {
                return $this->subscription->quranTeacher->user->name ?? $fallback;
            } elseif ($this->subscriptionType === 'academic') {
                return $this->subscription->academicTeacher->user->name ?? $fallback;
            } elseif ($this->subscriptionType === 'interactive') {
                return $this->subscription->course->assignedTeacher->full_name ?? $fallback;
            }
        }
        if ($this->circle) {
            return $this->circle->teacher->name ?? $fallback;
        }
        if ($this->course) {
            return $this->course->assignedTeacher->full_name ?? $fallback;
        }

        return $fallback;
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
