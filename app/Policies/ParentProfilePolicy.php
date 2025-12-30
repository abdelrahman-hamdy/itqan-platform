<?php

namespace App\Policies;

use App\Models\ParentProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for ParentProfile model.
 *
 * Handles authorization for parent-related actions.
 */
class ParentProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the parent dashboard.
     * Only parents can view the dashboard.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->user_type === 'parent' && $user->parentProfile !== null;
    }

    /**
     * Determine if the user can view any parent profiles (for admin purposes).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->user_type, ['admin', 'super_admin', 'supervisor']);
    }

    /**
     * Determine if the user can view the specified parent profile.
     */
    public function view(User $user, ParentProfile $parentProfile): bool
    {
        // Users can view their own parent profile
        if ($user->id === $parentProfile->user_id) {
            return true;
        }

        // Admins and supervisors can view any parent profile
        return in_array($user->user_type, ['admin', 'super_admin', 'supervisor']);
    }

    /**
     * Determine if the user can create a parent profile.
     */
    public function create(User $user): bool
    {
        // Users who don't have a parent profile yet can create one
        return $user->user_type === 'parent' && $user->parentProfile === null;
    }

    /**
     * Determine if the user can update the specified parent profile.
     */
    public function update(User $user, ParentProfile $parentProfile): bool
    {
        // Only the owner can update their profile
        return $user->id === $parentProfile->user_id;
    }

    /**
     * Determine if the user can delete the specified parent profile.
     */
    public function delete(User $user, ParentProfile $parentProfile): bool
    {
        // Only super_admins can delete parent profiles
        return $user->user_type === 'super_admin';
    }

    /**
     * Determine if the user can view children's data.
     */
    public function viewChildren(User $user, ParentProfile $parentProfile): bool
    {
        return $user->id === $parentProfile->user_id;
    }

    /**
     * Determine if the user can manage children (add/remove).
     */
    public function manageChildren(User $user, ParentProfile $parentProfile): bool
    {
        return $user->id === $parentProfile->user_id;
    }
}
