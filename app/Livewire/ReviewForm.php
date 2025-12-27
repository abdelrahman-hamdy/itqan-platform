<?php

namespace App\Livewire;

use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Services\ReviewService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Enums\SessionStatus;

class ReviewForm extends Component
{
    public $showModal = false;

    // Review type: 'teacher' or 'course'
    public $reviewType;

    // The model class being reviewed
    public $reviewableType;

    // The ID of the model being reviewed
    public $reviewableId;

    // Form fields
    public $rating = 0;
    public $comment = '';

    // State
    public $canReview = false;
    public $cannotReviewReason = '';
    public $existingReview = null;
    public $reviewableName = '';

    protected function rules()
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'rating.required' => 'يرجى اختيار تقييم',
        'rating.min' => 'يرجى اختيار تقييم',
        'rating.max' => 'التقييم يجب أن يكون بين 1 و 5',
        'comment.max' => 'التعليق يجب ألا يتجاوز 1000 حرف',
    ];

    public function mount($reviewType, $reviewableType, $reviewableId)
    {
        $this->reviewType = $reviewType;
        $this->reviewableType = $reviewableType;
        $this->reviewableId = $reviewableId;

        $this->checkCanReview();
    }

    protected function checkCanReview()
    {
        $user = Auth::user();
        if (!$user) {
            $this->canReview = false;
            $this->cannotReviewReason = 'يجب تسجيل الدخول';
            return;
        }

        $reviewable = $this->getReviewable();
        if (!$reviewable) {
            $this->canReview = false;
            $this->cannotReviewReason = 'لم يتم العثور على العنصر';
            return;
        }

        $this->reviewableName = $this->getReviewableName($reviewable);

        $reviewService = app(ReviewService::class);

        if ($this->reviewType === 'teacher') {
            $result = $reviewService->canReviewTeacher($user, $reviewable);
            $this->existingReview = $reviewService->getTeacherReview($user, $reviewable);
        } else {
            $result = $reviewService->canReviewCourse($user, $reviewable);
            $this->existingReview = $reviewService->getCourseReview($user, $reviewable);
        }

        $this->canReview = $result['can_review'];
        $this->cannotReviewReason = $result['reason'] ?? '';

        // If has existing review, populate fields
        if ($this->existingReview) {
            $this->rating = $this->existingReview->rating;
            $this->comment = $this->existingReview->comment ?? $this->existingReview->review ?? '';
        }
    }

    protected function getReviewable()
    {
        return match ($this->reviewableType) {
            QuranTeacherProfile::class => QuranTeacherProfile::find($this->reviewableId),
            AcademicTeacherProfile::class => AcademicTeacherProfile::find($this->reviewableId),
            RecordedCourse::class => RecordedCourse::find($this->reviewableId),
            InteractiveCourse::class => InteractiveCourse::find($this->reviewableId),
            default => null,
        };
    }

    protected function getReviewableName($reviewable): string
    {
        if ($reviewable instanceof QuranTeacherProfile || $reviewable instanceof AcademicTeacherProfile) {
            return $reviewable->full_name ?? 'المعلم';
        }

        if ($reviewable instanceof RecordedCourse || $reviewable instanceof InteractiveCourse) {
            return $reviewable->title ?? 'الدورة';
        }

        return '';
    }

    #[On('openReviewModal')]
    public function openModal()
    {
        $this->showModal = true;
        $this->checkCanReview();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function setRating($value)
    {
        $this->rating = $value;
    }

    public function submitReview()
    {
        $this->validate();

        $user = Auth::user();
        $reviewable = $this->getReviewable();

        if (!$user || !$reviewable) {
            session()->flash('error', 'حدث خطأ، يرجى المحاولة مرة أخرى');
            return;
        }

        try {
            $reviewService = app(ReviewService::class);

            if ($this->reviewType === 'teacher') {
                $reviewService->submitTeacherReview(
                    $user,
                    $reviewable,
                    $this->rating,
                    $this->comment ?: null
                );
            } else {
                $reviewService->submitCourseReview(
                    $user,
                    $reviewable,
                    $this->rating,
                    $this->comment ?: null
                );
            }

            $this->showModal = false;
            $this->dispatch('review-submitted');
            session()->flash('success', 'تم إرسال تقييمك بنجاح!');

            // Refresh the page to show updated review
            $this->redirect(request()->header('Referer'));

        } catch (\Exception $e) {
            session()->flash('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.review-form', [
            'stars' => range(1, 5),
        ]);
    }
}
