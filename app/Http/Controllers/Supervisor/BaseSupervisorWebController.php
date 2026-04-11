<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\SupervisorProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for Supervisor education frontend.
 *
 * Provides helper methods used by all supervisor/admin web controllers to
 * scope data consistently across the frontend.
 *
 * For super_admin and admin roles, returns ALL teachers/data in the academy
 * (no supervisor-based scoping). For supervisors, scopes to assigned teachers.
 */
abstract class BaseSupervisorWebController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:supervisor,super_admin,admin']);
    }

    // ========================================================================
    // Role check helpers
    // ========================================================================

    /**
     * Check if current user is an admin/super_admin (sees all academy data).
     */
    protected function isAdminUser(): bool
    {
        $user = Auth::user();

        return $user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isAcademyAdmin());
    }

    /**
     * Check if current user has teacher management permission.
     * Admin users always have this permission.
     */
    protected function canManageTeachers(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManageTeachers() ?? false;
    }

    /**
     * Check if current user has student management permission.
     * Admin users always have this permission.
     */
    protected function canManageStudents(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManageStudents() ?? false;
    }

    protected function canManageParents(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManageParents() ?? false;
    }

    protected function canResetPasswords(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canResetPasswords() ?? false;
    }

    protected function canManageSubscriptions(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManageSubscriptions() ?? false;
    }

    protected function canCreateSubscriptions(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canCreateSubscriptions() ?? false;
    }

    protected function canManagePayments(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManagePayments() ?? false;
    }

    protected function canManageTeacherEarnings(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManageTeacherEarnings() ?? false;
    }

    protected function canMonitorSessions(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canMonitorSessions() ?? false;
    }

    protected function canManageInteractiveCourses(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->getCurrentSupervisorProfile()?->canManageInteractiveCourses() ?? false;
    }

    // ========================================================================
    // Supervisor profile helpers
    // ========================================================================

    /**
     * Get the current user's supervisor profile.
     */
    protected function getCurrentSupervisorProfile(): ?SupervisorProfile
    {
        $user = Auth::user();

        return $user?->supervisorProfile;
    }

    /**
     * Get the current academy ID for scoping queries.
     */
    protected function getAcademyId(): int
    {
        $academy = AcademyContextService::getCurrentAcademy();
        if ($academy) {
            return $academy->id;
        }

        // Global view mode not supported on manage pages — auto-recover
        if (AcademyContextService::isGlobalViewMode()) {
            AcademyContextService::disableGlobalView();
        }
        AcademyContextService::initializeSuperAdminContext();
        $academy = AcademyContextService::getCurrentAcademy();

        return $academy?->id ?? abort(500, 'No academy context available.');
    }

    // ========================================================================
    // Teacher-based supervision – Quran
    // ========================================================================

    /**
     * Get the user IDs of Quran teachers.
     * Admin/SuperAdmin: returns ALL quran teachers in the academy.
     * Supervisor: returns only assigned quran teachers.
     *
     * @return int[]
     */
    protected function getAssignedQuranTeacherIds(): array
    {
        if ($this->isAdminUser()) {
            return User::where('user_type', UserType::QURAN_TEACHER->value)
                ->where('academy_id', $this->getAcademyId())
                ->pluck('id')
                ->toArray();
        }

        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getAssignedQuranTeacherIds() ?? [];
    }

    // ========================================================================
    // Teacher-based supervision – Academic
    // ========================================================================

    /**
     * Get the user IDs of Academic teachers.
     * Admin/SuperAdmin: returns ALL academic teachers in the academy.
     * Supervisor: returns only assigned academic teachers.
     *
     * @return int[]
     */
    protected function getAssignedAcademicTeacherIds(): array
    {
        if ($this->isAdminUser()) {
            return User::where('user_type', UserType::ACADEMIC_TEACHER->value)
                ->where('academy_id', $this->getAcademyId())
                ->pluck('id')
                ->toArray();
        }

        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getAssignedAcademicTeacherIds() ?? [];
    }

    /**
     * Get the QuranTeacherProfile IDs for Quran teachers.
     * Needed when filtering resources that reference the profile ID rather
     * than the user ID (e.g. QuranTrialRequest.teacher_id).
     *
     * @return int[]
     */
    protected function getAssignedQuranTeacherProfileIds(): array
    {
        if ($this->isAdminUser()) {
            return QuranTeacherProfile::pluck('id')->toArray();
        }

        $userIds = $this->getAssignedQuranTeacherIds();

        if (empty($userIds)) {
            return [];
        }

        return QuranTeacherProfile::whereIn('user_id', $userIds)
            ->pluck('id')
            ->toArray();
    }

    protected function getAssignedAcademicTeacherProfileIds(): array
    {
        if ($this->isAdminUser()) {
            return AcademicTeacherProfile::pluck('id')->toArray();
        }

        $userIds = $this->getAssignedAcademicTeacherIds();

        if (empty($userIds)) {
            return [];
        }

        return AcademicTeacherProfile::whereIn('user_id', $userIds)
            ->pluck('id')
            ->toArray();
    }

    // ========================================================================
    // Combined helpers
    // ========================================================================

    /**
     * Get all teacher IDs (both Quran and Academic user IDs).
     *
     * @return int[]
     */
    protected function getAllAssignedTeacherIds(): array
    {
        if ($this->isAdminUser()) {
            return User::whereIn('user_type', [
                UserType::QURAN_TEACHER->value,
                UserType::ACADEMIC_TEACHER->value,
            ])->pluck('id')->toArray();
        }

        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getAllAssignedTeacherIds() ?? [];
    }

    /**
     * Check if there are any teachers to display.
     */
    protected function hasAssignedTeachers(): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return ! empty($this->getAllAssignedTeacherIds());
    }

    // ========================================================================
    // Resource-based supervision (Interactive Courses)
    // ========================================================================

    /**
     * Get IDs of resources the supervisor is directly responsible for.
     *
     * @return int[]
     */
    protected function getResponsibleResourceIds(string $modelClass): array
    {
        if ($this->isAdminUser()) {
            return $modelClass::pluck('id')->toArray();
        }

        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getResponsibilityIds($modelClass) ?? [];
    }

    /**
     * Get interactive course IDs derived from academic teachers.
     *
     * @return int[]
     */
    protected function getDerivedInteractiveCourseIds(): array
    {
        if ($this->isAdminUser()) {
            return []; // Admin already gets all via getResponsibleResourceIds
        }

        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getDerivedInteractiveCourseIds() ?? [];
    }
}
