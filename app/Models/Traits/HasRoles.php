<?php

namespace App\Models\Traits;

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
        return $this->user_type === 'student';
    }

    public function isQuranTeacher(): bool
    {
        return $this->user_type === 'quran_teacher';
    }

    public function isAcademicTeacher(): bool
    {
        return $this->user_type === 'academic_teacher';
    }

    public function isParent(): bool
    {
        return $this->user_type === 'parent';
    }

    public function isSupervisor(): bool
    {
        return $this->user_type === 'supervisor';
    }

    public function isAdmin(): bool
    {
        return in_array($this->user_type, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin';
    }

    public function isAcademyAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if this admin user is assigned to manage an academy
     */
    public function isAssignedToAcademy(): bool
    {
        return $this->user_type === 'admin' && $this->academy_id !== null;
    }

    /**
     * Get the academy this admin is assigned to manage
     * Returns null if user is not an admin or not assigned
     */
    public function getAdministratedAcademy(): ?\App\Models\Academy
    {
        if ($this->user_type !== 'admin') {
            return null;
        }

        return \App\Models\Academy::where('admin_id', $this->id)->first();
    }

    /**
     * Check if user is a teacher (any type)
     */
    public function isTeacher(): bool
    {
        return in_array($this->user_type, ['quran_teacher', 'academic_teacher']);
    }

    /**
     * Check if user is staff (admin, supervisor, or teacher)
     */
    public function isStaff(): bool
    {
        return in_array($this->user_type, ['admin', 'super_admin', 'supervisor', 'quran_teacher', 'academic_teacher']);
    }

    /**
     * Check if user is end user (student or parent)
     */
    public function isEndUser(): bool
    {
        return in_array($this->user_type, ['student', 'parent']);
    }

    /**
     * Check if user can access dashboard (power users)
     */
    public function canAccessDashboard(): bool
    {
        return in_array($this->user_type, [
            'admin',
            'supervisor',
            'quran_teacher',
            'academic_teacher',
        ]);
    }

    /**
     * Get dashboard route based on user type
     */
    public function getDashboardRoute(): string
    {
        return match ($this->user_type) {
            'admin' => '/panel',
            'supervisor' => '/supervisor',
            'quran_teacher', 'academic_teacher' => '/teacher',
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
        return $query->whereIn('user_type', ['admin', 'supervisor', 'quran_teacher', 'academic_teacher']);
    }

    /**
     * Scope to get end users (students and parents)
     */
    public function scopeEndUsers($query): Builder
    {
        return $query->whereIn('user_type', ['student', 'parent']);
    }
}
