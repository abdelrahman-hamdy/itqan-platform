<?php

namespace App\Models\Traits;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;

trait HasRoles
{
    /**
     * User roles constants
     */
    const ROLE_SUPER_ADMIN = 'super_admin';

    const ROLE_ACADEMY_ADMIN = 'admin';

    const ROLE_QURAN_TEACHER = 'quran_teacher';

    const ROLE_ACADEMIC_TEACHER = 'academic_teacher';

    const ROLE_SUPERVISOR = 'supervisor';

    const ROLE_STUDENT = 'student';

    const ROLE_PARENT = 'parent';

    /**
     * Check if user has any of the specified roles
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($this->user_type, $roles);
    }

    /**
     * User type helper methods
     */
    public function isStudent(): bool
    {
        return $this->user_type === UserType::STUDENT->value;
    }

    public function isQuranTeacher(): bool
    {
        return $this->user_type === UserType::QURAN_TEACHER->value;
    }

    public function isAcademicTeacher(): bool
    {
        return $this->user_type === UserType::ACADEMIC_TEACHER->value;
    }

    public function isParent(): bool
    {
        return $this->user_type === UserType::PARENT->value;
    }

    public function isSupervisor(): bool
    {
        return $this->user_type === UserType::SUPERVISOR->value;
    }

    public function isAdmin(): bool
    {
        return in_array($this->user_type, [UserType::ADMIN->value, UserType::SUPER_ADMIN->value]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === UserType::SUPER_ADMIN->value;
    }

    public function isAcademyAdmin(): bool
    {
        return $this->user_type === UserType::ADMIN->value;
    }

    /**
     * Check if this admin user is assigned to manage an academy
     */
    public function isAssignedToAcademy(): bool
    {
        return $this->user_type === UserType::ADMIN->value && $this->academy_id !== null;
    }

    /**
     * Get the academy this admin is assigned to manage
     * Returns null if user is not an admin or not assigned
     */
    public function getAdministratedAcademy(): ?\App\Models\Academy
    {
        if ($this->user_type !== UserType::ADMIN->value) {
            return null;
        }

        return \App\Models\Academy::where('admin_id', $this->id)->first();
    }

    /**
     * Check if user is a teacher (any type)
     */
    public function isTeacher(): bool
    {
        return in_array($this->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Check if user is staff (admin, supervisor, or teacher)
     */
    public function isStaff(): bool
    {
        return in_array($this->user_type, [UserType::ADMIN->value, UserType::SUPER_ADMIN->value, UserType::SUPERVISOR->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Check if user is end user (student or parent)
     */
    public function isEndUser(): bool
    {
        return in_array($this->user_type, [UserType::STUDENT->value, UserType::PARENT->value]);
    }

    /**
     * Check if user can access dashboard (power users)
     */
    public function canAccessDashboard(): bool
    {
        return in_array($this->user_type, [
            UserType::ADMIN->value,
            UserType::SUPERVISOR->value,
            UserType::QURAN_TEACHER->value,
            UserType::ACADEMIC_TEACHER->value,
        ]);
    }

    /**
     * Get dashboard route based on user type
     */
    public function getDashboardRoute(): string
    {
        return match ($this->user_type) {
            UserType::ADMIN->value => '/panel',
            UserType::SUPERVISOR->value => '/supervisor',
            UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value => '/teacher',
            default => '/profile',
        };
    }

    /**
     * Get user type label (localized)
     */
    public function getUserTypeLabel(): string
    {
        $translationKey = 'enums.user_type.'.$this->user_type;
        $translated = __($translationKey);

        // Return translated value if translation exists, otherwise return the raw user_type
        return $translated !== $translationKey ? $translated : $this->user_type;
    }

    /**
     * Scope to filter by user type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope to get dashboard users (power users)
     */
    public function scopeDashboardUsers($query): Builder
    {
        return $query->whereIn('user_type', [UserType::ADMIN->value, UserType::SUPERVISOR->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Scope to get end users (students and parents)
     */
    public function scopeEndUsers($query): Builder
    {
        return $query->whereIn('user_type', [UserType::STUDENT->value, UserType::PARENT->value]);
    }
}
