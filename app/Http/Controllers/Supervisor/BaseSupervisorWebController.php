<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AcademicTeacherProfile;
use App\Models\SupervisorProfile;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for Supervisor education frontend.
 *
 * Provides helper methods that mirror the scoping logic in
 * App\Filament\Supervisor\Resources\BaseSupervisorResource so that
 * the web frontend and Filament panel share identical data boundaries.
 */
abstract class BaseSupervisorWebController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:supervisor,super_admin']);
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
        return AcademyContextService::getCurrentAcademy()->id;
    }

    // ========================================================================
    // Teacher-based supervision – Quran
    // ========================================================================

    /**
     * Get the user IDs of Quran teachers assigned to this supervisor.
     *
     * @return int[]
     */
    protected function getAssignedQuranTeacherIds(): array
    {
        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getAssignedQuranTeacherIds() ?? [];
    }

    // ========================================================================
    // Teacher-based supervision – Academic
    // ========================================================================

    /**
     * Get the user IDs of Academic teachers assigned to this supervisor.
     *
     * @return int[]
     */
    protected function getAssignedAcademicTeacherIds(): array
    {
        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getAssignedAcademicTeacherIds() ?? [];
    }

    /**
     * Get the AcademicTeacherProfile IDs for assigned academic teachers.
     * Needed when filtering resources that reference the profile ID rather
     * than the user ID (e.g. AcademicSession.academic_teacher_id).
     *
     * @return int[]
     */
    protected function getAssignedAcademicTeacherProfileIds(): array
    {
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
     * Get all assigned teacher IDs (both Quran and Academic user IDs).
     *
     * @return int[]
     */
    protected function getAllAssignedTeacherIds(): array
    {
        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getAllAssignedTeacherIds() ?? [];
    }

    /**
     * Check if the supervisor has any assigned teachers at all.
     */
    protected function hasAssignedTeachers(): bool
    {
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
        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getResponsibilityIds($modelClass) ?? [];
    }

    /**
     * Get interactive course IDs derived from assigned academic teachers.
     *
     * @return int[]
     */
    protected function getDerivedInteractiveCourseIds(): array
    {
        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getDerivedInteractiveCourseIds() ?? [];
    }
}
