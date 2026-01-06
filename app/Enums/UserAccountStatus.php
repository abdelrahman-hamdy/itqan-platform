<?php

namespace App\Enums;

/**
 * UserAccountStatus Enum
 *
 * Status for user accounts (admins, teachers, students, parents, supervisors).
 * This is separate from subscription status and session status.
 *
 * Lifecycle:
 * - PENDING -> ACTIVE (email verified / admin approval)
 * - ACTIVE -> INACTIVE (manual deactivation)
 * - ACTIVE -> SUSPENDED (disciplinary action)
 * - INACTIVE/SUSPENDED -> ACTIVE (reactivation)
 *
 * @see \App\Models\User
 */
enum UserAccountStatus: string
{
    case ACTIVE = 'active';         // Account is active and can login
    case INACTIVE = 'inactive';     // Account is deactivated
    case PENDING = 'pending';       // Awaiting verification/approval
    case SUSPENDED = 'suspended';   // Temporarily blocked (disciplinary)

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.user_account_status.'.$this->value);
    }

    /**
     * Get English label
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::PENDING => 'Pending',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Get Filament color
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
            self::PENDING => 'warning',
            self::SUSPENDED => 'gray',
        };
    }

    /**
     * Get icon (Heroicons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::INACTIVE => 'heroicon-o-x-circle',
            self::PENDING => 'heroicon-o-clock',
            self::SUSPENDED => 'heroicon-o-exclamation-triangle',
        };
    }

    /**
     * Get Tailwind badge classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::INACTIVE => 'bg-red-100 text-red-800',
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::SUSPENDED => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if user can login
     */
    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if account can be activated
     */
    public function canActivate(): bool
    {
        return in_array($this, [self::INACTIVE, self::PENDING, self::SUSPENDED]);
    }

    /**
     * Check if account can be deactivated
     */
    public function canDeactivate(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if account can be suspended
     */
    public function canSuspend(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get valid next statuses from current status
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVE, self::INACTIVE],
            self::ACTIVE => [self::INACTIVE, self::SUSPENDED],
            self::INACTIVE => [self::ACTIVE],
            self::SUSPENDED => [self::ACTIVE, self::INACTIVE],
        };
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms (value => label)
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Get active statuses (can login)
     */
    public static function activeStatuses(): array
    {
        return [self::ACTIVE];
    }
}
