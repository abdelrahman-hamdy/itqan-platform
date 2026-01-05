<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
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
}
