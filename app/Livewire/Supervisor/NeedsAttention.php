<?php

namespace App\Livewire\Supervisor;

use App\Enums\UserType;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseReview;
use App\Models\TeacherReview;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\DashboardAttentionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NeedsAttention extends Component
{
    public array $groups = [];

    public int $totalCount = 0;

    public string $worstSeverity = 'clear';

    public array $pendingReviews = [];

    public bool $showReviewsPanel = false;

    public array $unconfirmedStudents = [];

    public bool $showUnconfirmedPanel = false;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $academyId = AcademyContextService::getCurrentAcademy()?->id;
        if (! $academyId) {
            return;
        }

        $isAdmin = $this->isAdminUser();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $service = app(DashboardAttentionService::class);
        $data = $service->getAttentionItems($academyId, $isAdmin, $quranTeacherIds, $academicTeacherProfileIds, $this->canConfirmStudentEmails());

        $this->groups = $data['groups'];
        $this->totalCount = $data['total_count'];
        $this->worstSeverity = $data['worst_severity'];
        $this->pendingReviews = $data['pendingReviews'];
        $this->unconfirmedStudents = $data['unconfirmedStudents'];
    }

    public function approveReview(string $type, int $id): void
    {
        $academyId = AcademyContextService::getCurrentAcademy()?->id;
        if (! $academyId) {
            return;
        }

        if ($type === 'course') {
            $review = CourseReview::where('academy_id', $academyId)->find($id);
        } else {
            $review = TeacherReview::where('academy_id', $academyId)->find($id);
        }

        if (! $review) {
            return;
        }

        $review->approve(Auth::id());
        $this->clearCacheAndReload($academyId);
    }

    public function deleteReview(string $type, int $id): void
    {
        $academyId = AcademyContextService::getCurrentAcademy()?->id;
        if (! $academyId) {
            return;
        }

        if ($type === 'course') {
            $review = CourseReview::where('academy_id', $academyId)->find($id);
        } else {
            $review = TeacherReview::where('academy_id', $academyId)->find($id);
        }

        if (! $review) {
            return;
        }

        $review->delete();
        $this->clearCacheAndReload($academyId);
    }

    public function toggleReviewsPanel(): void
    {
        $this->showReviewsPanel = ! $this->showReviewsPanel;
    }

    public function confirmStudentEmail(int $userId): void
    {
        if (! $this->canConfirmStudentEmails()) {
            return;
        }

        $academyId = AcademyContextService::getCurrentAcademy()?->id;
        if (! $academyId) {
            return;
        }

        $user = User::where('academy_id', $academyId)
            ->where('user_type', UserType::STUDENT->value)
            ->whereNull('email_verified_at')
            ->where('active_status', true)
            ->find($userId);

        if (! $user) {
            return;
        }

        $user->markEmailAsVerified();
        $this->clearCacheAndReload($academyId);
    }

    public function toggleUnconfirmedPanel(): void
    {
        $this->showUnconfirmedPanel = ! $this->showUnconfirmedPanel;
    }

    private function clearCacheAndReload(int $academyId): void
    {
        $service = app(DashboardAttentionService::class);
        $service->forgetCacheFor(
            $academyId,
            $this->getAssignedQuranTeacherIds(),
            $this->getAssignedAcademicTeacherProfileIds(),
            $this->canConfirmStudentEmails(),
        );

        $this->loadData();
    }

    // ========================================================================
    // Scoping helpers (mirrors BaseSupervisorWebController)
    // ========================================================================

    private function isAdminUser(): bool
    {
        $user = Auth::user();

        return $user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isAcademyAdmin());
    }

    private function canConfirmStudentEmails(): bool
    {
        return $this->isAdminUser()
            || (Auth::user()?->supervisorProfile?->canConfirmStudentEmails() ?? false);
    }

    private function getAssignedQuranTeacherIds(): array
    {
        if ($this->isAdminUser()) {
            return User::where('user_type', UserType::QURAN_TEACHER->value)
                ->pluck('id')
                ->toArray();
        }

        return Auth::user()?->supervisorProfile?->getAssignedQuranTeacherIds() ?? [];
    }

    private function getAssignedAcademicTeacherProfileIds(): array
    {
        if ($this->isAdminUser()) {
            return AcademicTeacherProfile::pluck('id')->toArray();
        }

        $userIds = Auth::user()?->supervisorProfile?->getAssignedAcademicTeacherIds() ?? [];

        if (empty($userIds)) {
            return [];
        }

        return AcademicTeacherProfile::whereIn('user_id', $userIds)
            ->pluck('id')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.supervisor.needs-attention');
    }
}
