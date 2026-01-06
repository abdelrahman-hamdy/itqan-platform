<?php

namespace App\Enums;

/**
 * User Type Enum
 *
 * Defines all user roles in the Itqan Platform.
 * Each user has a single user_type that determines their access level and functionality.
 *
 * User types are hierarchical:
 * - Power Users: super_admin, admin, supervisor
 * - Teaching Staff: quran_teacher, academic_teacher
 * - End Users: student, parent
 *
 * @see \App\Models\User
 * @see \App\Models\Traits\HasRoles
 */
enum UserType: string
{
    case STUDENT = 'student';                      // Student/learner
    case PARENT = 'parent';                        // Parent/guardian
    case QURAN_TEACHER = 'quran_teacher';          // Quran teacher
    case ACADEMIC_TEACHER = 'academic_teacher';    // Academic subject teacher
    case SUPERVISOR = 'supervisor';                // Academy supervisor
    case ADMIN = 'admin';                          // Academy admin (also: academy_admin)
    case SUPER_ADMIN = 'super_admin';              // System super admin

    /**
     * Get the localized label for the user type
     */
    public function label(): string
    {
        return __('enums.user_type.'.$this->value);
    }

    /**
     * Get the icon for the user type
     */
    public function icon(): string
    {
        return match ($this) {
            self::STUDENT => 'ri-user-line',
            self::PARENT => 'ri-parent-line',
            self::QURAN_TEACHER => 'ri-book-2-line',
            self::ACADEMIC_TEACHER => 'ri-book-open-line',
            self::SUPERVISOR => 'ri-user-settings-line',
            self::ADMIN => 'ri-admin-line',
            self::SUPER_ADMIN => 'ri-shield-star-line',
        };
    }

    /**
     * Get the Filament color class for the user type
     */
    public function color(): string
    {
        return match ($this) {
            self::STUDENT => 'info',
            self::PARENT => 'warning',
            self::QURAN_TEACHER => 'success',
            self::ACADEMIC_TEACHER => 'primary',
            self::SUPERVISOR => 'warning',
            self::ADMIN => 'danger',
            self::SUPER_ADMIN => 'danger',
        };
    }

    /**
     * Check if user type is a teacher (any kind)
     */
    public function isTeacher(): bool
    {
        return in_array($this, [self::QURAN_TEACHER, self::ACADEMIC_TEACHER]);
    }

    /**
     * Check if user type is staff (admin, supervisor, or teacher)
     */
    public function isStaff(): bool
    {
        return in_array($this, [
            self::ADMIN,
            self::SUPER_ADMIN,
            self::SUPERVISOR,
            self::QURAN_TEACHER,
            self::ACADEMIC_TEACHER,
        ]);
    }

    /**
     * Check if user type is end user (student or parent)
     */
    public function isEndUser(): bool
    {
        return in_array($this, [self::STUDENT, self::PARENT]);
    }

    /**
     * Check if user type can access admin dashboard
     */
    public function canAccessDashboard(): bool
    {
        return in_array($this, [
            self::ADMIN,
            self::SUPER_ADMIN,
            self::SUPERVISOR,
            self::QURAN_TEACHER,
            self::ACADEMIC_TEACHER,
        ]);
    }

    /**
     * Get dashboard route for user type
     */
    public function getDashboardRoute(): string
    {
        return match ($this) {
            self::ADMIN, self::SUPER_ADMIN => '/panel',
            self::SUPERVISOR => '/supervisor',
            self::QURAN_TEACHER, self::ACADEMIC_TEACHER => '/teacher',
            default => '/profile',
        };
    }

    /**
     * Get all user type values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get user type options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($type) => $type->label(), self::cases())
        );
    }

    /**
     * Get staff user types
     */
    public static function staffTypes(): array
    {
        return [
            self::ADMIN,
            self::SUPER_ADMIN,
            self::SUPERVISOR,
            self::QURAN_TEACHER,
            self::ACADEMIC_TEACHER,
        ];
    }

    /**
     * Get end user types
     */
    public static function endUserTypes(): array
    {
        return [self::STUDENT, self::PARENT];
    }

    /**
     * Get teacher types
     */
    public static function teacherTypes(): array
    {
        return [self::QURAN_TEACHER, self::ACADEMIC_TEACHER];
    }
}
