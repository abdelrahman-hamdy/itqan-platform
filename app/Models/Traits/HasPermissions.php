<?php

namespace App\Models\Traits;

use App\Enums\UserType;
use Filament\Panel;

trait HasPermissions
{
    /**
     * Filament User Interface Implementation
     *
     * Only two Filament panels remain: `admin` (super-admin system panel)
     * and `academy` (academy admin panel). Teachers and supervisors use the
     * frontend panels under `/teacher` and `/manage` respectively.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return match ($panel->getId()) {
            'academy' => $this->user_type === UserType::ADMIN->value,
            default => false,
        };
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
