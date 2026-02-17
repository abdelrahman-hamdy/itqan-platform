<?php

namespace App\Services;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service to maintain bidirectional sync between Academy.admin_id and User.academy_id
 *
 * This ensures data integrity by keeping both relationships in sync:
 * - When Academy.admin_id changes -> User.academy_id is updated
 * - When User.academy_id changes (for admin users) -> Academy.admin_id is updated
 */
class AcademyAdminSyncService
{
    /**
     * Flag to prevent recursive sync calls
     */
    private static bool $syncing = false;

    /**
     * Sync when Academy.admin_id changes
     *
     * @param  Academy  $academy  The academy being updated
     * @param  int|null  $newAdminId  The new admin user ID
     * @param  int|null  $oldAdminId  The previous admin user ID
     */
    public function syncFromAcademy(Academy $academy, ?int $newAdminId, ?int $oldAdminId): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        try {
            DB::transaction(function () use ($academy, $newAdminId, $oldAdminId) {
                // Clear old admin's academy_id if they were assigned to this academy
                if ($oldAdminId && $oldAdminId !== $newAdminId) {
                    $oldAdmin = User::find($oldAdminId);
                    if ($oldAdmin && $oldAdmin->academy_id === $academy->id) {
                        $oldAdmin->timestamps = false;
                        $oldAdmin->update(['academy_id' => null]);
                        Log::info('AcademyAdminSync: Cleared academy_id for old admin', [
                            'user_id' => $oldAdminId,
                            'academy_id' => $academy->id,
                        ]);
                    }
                }

                // Set new admin's academy_id to this academy
                if ($newAdminId) {
                    $newAdmin = User::find($newAdminId);
                    if ($newAdmin) {
                        // Validate user is an admin
                        if ($newAdmin->user_type !== UserType::ADMIN->value) {
                            throw new InvalidArgumentException("User ID {$newAdminId} is not an admin user");
                        }

                        // Clear any other academy's admin_id if this admin was previously assigned elsewhere
                        if ($newAdmin->academy_id && $newAdmin->academy_id !== $academy->id) {
                            Academy::where('admin_id', $newAdminId)
                                ->where('id', '!=', $academy->id)
                                ->update(['admin_id' => null]);
                        }

                        // Update the admin's academy_id
                        $newAdmin->timestamps = false;
                        $newAdmin->update(['academy_id' => $academy->id]);
                        Log::info('AcademyAdminSync: Set academy_id for new admin', [
                            'user_id' => $newAdminId,
                            'academy_id' => $academy->id,
                        ]);
                    }
                }
            });
        } finally {
            self::$syncing = false;
        }
    }

    /**
     * Sync when User.academy_id changes (for admin users)
     *
     * @param  User  $user  The admin user being updated
     * @param  int|null  $newAcademyId  The new academy ID
     * @param  int|null  $oldAcademyId  The previous academy ID
     */
    public function syncFromUser(User $user, ?int $newAcademyId, ?int $oldAcademyId): void
    {
        if (self::$syncing) {
            return;
        }

        // Only sync for admin users
        if ($user->user_type !== UserType::ADMIN->value) {
            return;
        }

        self::$syncing = true;

        try {
            DB::transaction(function () use ($user, $newAcademyId, $oldAcademyId) {
                // Clear old academy's admin_id if this user was the admin
                if ($oldAcademyId && $oldAcademyId !== $newAcademyId) {
                    $oldAcademy = Academy::find($oldAcademyId);
                    if ($oldAcademy && $oldAcademy->admin_id === $user->id) {
                        $oldAcademy->timestamps = false;
                        $oldAcademy->update(['admin_id' => null]);
                        Log::info('AcademyAdminSync: Cleared admin_id for old academy', [
                            'academy_id' => $oldAcademyId,
                            'user_id' => $user->id,
                        ]);
                    }
                }

                // Set new academy's admin_id to this user
                if ($newAcademyId) {
                    $newAcademy = Academy::find($newAcademyId);
                    if ($newAcademy) {
                        // Check if the new academy already has a different admin
                        if ($newAcademy->admin_id && $newAcademy->admin_id !== $user->id) {
                            // Clear the previous admin's academy_id
                            User::where('id', $newAcademy->admin_id)
                                ->update(['academy_id' => null]);
                        }

                        // Update the academy's admin_id
                        $newAcademy->timestamps = false;
                        $newAcademy->update(['admin_id' => $user->id]);
                        Log::info('AcademyAdminSync: Set admin_id for new academy', [
                            'academy_id' => $newAcademyId,
                            'user_id' => $user->id,
                        ]);
                    }
                }
            });
        } finally {
            self::$syncing = false;
        }
    }

    /**
     * Check if an admin can be assigned to an academy
     *
     * @param  User  $admin  The admin user to check
     * @param  Academy  $academy  The academy to check
     * @return bool True if assignment is valid
     */
    public function canAssign(User $admin, Academy $academy): bool
    {
        // User must be an admin
        if ($admin->user_type !== UserType::ADMIN->value) {
            return false;
        }

        // If admin is already assigned to this academy, it's valid
        if ($admin->academy_id === $academy->id || $academy->admin_id === $admin->id) {
            return true;
        }

        // Admin must not be assigned to another academy
        if ($admin->academy_id !== null) {
            return false;
        }

        // Academy must not have another admin
        if ($academy->admin_id !== null) {
            return false;
        }

        return true;
    }

    /**
     * Get academies available for assignment to an admin
     *
     * @param  User|null  $currentAdmin  The current admin (to include their assigned academy)
     */
    public function getAvailableAcademies(?User $currentAdmin = null): Collection
    {
        return Academy::active()
            ->where(function ($query) use ($currentAdmin) {
                $query->whereNull('admin_id');
                if ($currentAdmin && $currentAdmin->id) {
                    $query->orWhere('admin_id', $currentAdmin->id);
                }
            })
            ->get();
    }

    /**
     * Get admins available for assignment to an academy
     *
     * @param  Academy|null  $currentAcademy  The current academy (to include its assigned admin)
     */
    public function getAvailableAdmins(?Academy $currentAcademy = null): Collection
    {
        return User::where('user_type', UserType::ADMIN->value)
            ->where(function ($query) use ($currentAcademy) {
                $query->whereNull('academy_id');
                if ($currentAcademy && $currentAcademy->admin_id) {
                    $query->orWhere('id', $currentAcademy->admin_id);
                }
            })
            ->get();
    }

    /**
     * Check if syncing is currently in progress
     */
    public static function isSyncing(): bool
    {
        return self::$syncing;
    }
}
