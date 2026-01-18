<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AcademyAdminSyncService;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function __construct(
        protected AcademyAdminSyncService $syncService
    ) {}

    /**
     * Handle the User "creating" event.
     * Auto-verify email when user is created by admin/supervisor via Filament.
     */
    public function creating(User $user): void
    {
        // Check if this is being created in admin context (Filament)
        $currentUser = Auth::user();

        // If created by an admin, supervisor, or super_admin, auto-verify email
        if ($currentUser && in_array($currentUser->user_type, ['super_admin', 'admin', 'supervisor'])) {
            // Admin-created users are auto-verified
            $user->email_verified_at = now();
        }
    }

    /**
     * Handle the User "created" event.
     * Sync academy relationship for newly created admin users with academy_id set.
     */
    public function created(User $user): void
    {
        if ($user->user_type === 'admin' && $user->academy_id && ! AcademyAdminSyncService::isSyncing()) {
            $this->syncService->syncFromUser(
                $user,
                $user->academy_id,
                null
            );
        }
    }

    /**
     * Handle the User "updating" event.
     * Sync academy relationship when academy_id changes for admin users.
     */
    public function updating(User $user): void
    {
        if ($user->user_type === 'admin' && $user->isDirty('academy_id') && ! AcademyAdminSyncService::isSyncing()) {
            $this->syncService->syncFromUser(
                $user,
                $user->academy_id,
                $user->getOriginal('academy_id')
            );
        }
    }
}
