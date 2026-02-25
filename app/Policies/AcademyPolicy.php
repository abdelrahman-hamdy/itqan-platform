<?php

namespace App\Policies;

use App\Constants\DefaultAcademy;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\User;

/**
 * Academy Policy
 *
 * Authorization policy for academy access and management.
 * Controls who can view, create, update, and manage academy settings.
 */
class AcademyPolicy
{
    /**
     * Determine whether the user can view any academies.
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all academies
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Admins can view their own academy (the index is further scoped by global scope)
        return $user->hasRole(UserType::ADMIN->value);
    }

    /**
     * Determine whether the user can view the academy.
     */
    public function view(User $user, Academy $academy): bool
    {
        // Super admins can view any academy
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Users can view their own academy
        if ($user->academy_id === $academy->id) {
            return true;
        }

        // Academy admin can view their academy
        if ($academy->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create academies.
     */
    public function create(User $user): bool
    {
        // Only super admins can create new academies
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can update the academy.
     */
    public function update(User $user, Academy $academy): bool
    {
        // Super admins can update any academy
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Academy admin can update their academy
        if ($user->hasRole(UserType::ADMIN->value) && $user->academy_id === $academy->id) {
            return true;
        }

        // Academy owner can update
        if ($academy->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the academy.
     */
    public function delete(User $user, Academy $academy): bool
    {
        // Only super admins can delete academies
        if (! $user->hasRole(UserType::SUPER_ADMIN->value)) {
            return false;
        }

        // Cannot delete the default academy
        if ($academy->subdomain === DefaultAcademy::subdomain()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the academy.
     */
    public function restore(User $user, Academy $academy): bool
    {
        // Only super admins can restore academies
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can permanently delete the academy.
     */
    public function forceDelete(User $user, Academy $academy): bool
    {
        // Only super admins can permanently delete academies
        if (! $user->hasRole(UserType::SUPER_ADMIN->value)) {
            return false;
        }

        // Cannot delete default academy
        if ($academy->subdomain === DefaultAcademy::subdomain()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage academy settings.
     */
    public function manageSettings(User $user, Academy $academy): bool
    {
        // Super admins can manage any academy settings
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Academy admin can manage their academy settings
        if ($user->hasRole(UserType::ADMIN->value) && $user->academy_id === $academy->id) {
            return true;
        }

        // Academy owner can manage settings
        if ($academy->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage academy branding.
     */
    public function manageBranding(User $user, Academy $academy): bool
    {
        // Same as manageSettings
        return $this->manageSettings($user, $academy);
    }

    /**
     * Determine whether the user can manage academy design.
     */
    public function manageDesign(User $user, Academy $academy): bool
    {
        // Same as manageSettings
        return $this->manageSettings($user, $academy);
    }

    /**
     * Determine whether the user can view academy financial data.
     */
    public function viewFinancials(User $user, Academy $academy): bool
    {
        // Super admins can view any academy financials
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Academy admin can view their academy financials
        if ($user->hasRole(UserType::ADMIN->value) && $user->academy_id === $academy->id) {
            return true;
        }

        // Academy owner can view financials
        if ($academy->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage academy users.
     */
    public function manageUsers(User $user, Academy $academy): bool
    {
        // Super admins can manage users in any academy
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Academy admin can manage their academy users
        if ($user->hasRole(UserType::ADMIN->value) && $user->academy_id === $academy->id) {
            return true;
        }

        // Academy owner can manage users
        if ($academy->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can toggle academy maintenance mode.
     */
    public function toggleMaintenanceMode(User $user, Academy $academy): bool
    {
        // Only super admins and academy admins can toggle maintenance mode
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserType::ADMIN->value) && $user->academy_id === $academy->id) {
            return true;
        }

        return false;
    }
}
