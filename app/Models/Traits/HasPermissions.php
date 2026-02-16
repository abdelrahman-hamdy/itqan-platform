<?php

namespace App\Models\Traits;

use App\Enums\UserType;
use Filament\Panel;

trait HasPermissions
{
    /**
     * Filament User Interface Implementation
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Super admins can access ALL panels
        if ($this->isSuperAdmin()) {
            return true;
        }

        // For regular users, check specific panel permissions
        switch ($panel->getId()) {
            case 'admin':
                return false; // Only super admins can access admin panel

            case 'academy':
                return $this->user_type === UserType::ADMIN->value;

            case 'teacher':
                // Only Quran teachers can access the teacher panel
                return $this->user_type === UserType::QURAN_TEACHER->value;

            case 'academic-teacher':
                // Only academic teachers can access the academic teacher panel
                return $this->user_type === UserType::ACADEMIC_TEACHER->value;

            case 'supervisor':
                return $this->isSupervisor();

            default:
                return false;
        }
    }

    /**
     * Check if user can create groups
     * Custom method for permission checking
     */
    public function canCreateGroups(): bool
    {
        // Allow teachers, admins, and supervisors to create groups
        return in_array($this->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::SUPERVISOR->value, UserType::ADMIN->value, UserType::SUPER_ADMIN->value]);
    }

    /**
     * Check if user can create chats
     * Custom method for permission checking
     */
    public function canCreateChats(): bool
    {
        // Allow all active authenticated users to create chats
        // Email/phone verification not required as platform doesn't enforce it
        return (bool) $this->active_status;
    }
}
