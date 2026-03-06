<?php

namespace App\Models\Traits;

use App\Models\User;

/**
 * Cascades soft-delete and restore operations from a profile model to its associated User.
 *
 * When a profile is soft-deleted (from Filament, API, or anywhere), the linked User
 * is also soft-deleted so they can no longer log in. Restoring the profile restores the User.
 *
 * Requires the model to have a `user_id` column referencing `users.id`.
 */
trait CascadesSoftDeleteToUser
{
    public static function bootCascadesSoftDeleteToUser(): void
    {
        // When profile is soft-deleted, also soft-delete the User
        static::deleted(function ($profile) {
            if (! $profile->isForceDeleting() && $profile->user_id) {
                // Query by FK directly to avoid scope interference
                User::where('id', $profile->user_id)->first()?->delete();
            }
        });

        // When profile is restored, also restore the User
        static::restored(function ($profile) {
            if ($profile->user_id) {
                User::withTrashed()->where('id', $profile->user_id)->first()?->restore();
            }
        });

        // When profile is force-deleted, also force-delete the User
        static::forceDeleted(function ($profile) {
            if ($profile->user_id) {
                User::withTrashed()->where('id', $profile->user_id)->first()?->forceDelete();
            }
        });
    }
}
