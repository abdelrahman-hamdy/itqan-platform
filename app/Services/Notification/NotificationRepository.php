<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repository for notification database operations.
 *
 * Handles all CRUD operations for notifications including
 * creating, reading, updating (marking read), and deleting.
 */
class NotificationRepository
{
    /**
     * Create a notification in the database.
     *
     * @param User $user The user to notify
     * @param array $data The notification data
     * @return string The notification ID
     */
    public function create(User $user, array $data): string
    {
        $id = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $data['type_class'] ?? NotificationService::class . '\\' . $data['type'],
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => json_encode($data['data'] ?? []),
            'notification_type' => $data['type'] ?? null,
            'category' => $data['category'] ?? null,
            'icon' => $data['icon'] ?? null,
            'icon_color' => $data['icon_color'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'is_important' => $data['is_important'] ?? false,
            'tenant_id' => $data['tenant_id'] ?? $user->academy_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Mark a notification as read.
     *
     * @param string $notificationId The notification ID
     * @param User $user The notification owner
     * @return bool Whether the update was successful
     */
    public function markAsRead(string $notificationId, User $user): bool
    {
        // First, check if panel_opened_at is null and set it along with read_at
        $notification = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->first(['panel_opened_at']);

        if (!$notification) {
            return false;
        }

        $updateData = ['read_at' => now()];

        // Only set panel_opened_at if it's currently null
        if (is_null($notification->panel_opened_at)) {
            $updateData['panel_opened_at'] = now();
        }

        return DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->update($updateData) > 0;
    }

    /**
     * Mark all notifications as panel-opened (seen in panel but not clicked).
     *
     * @param User $user The notification owner
     * @return int Number of notifications updated
     */
    public function markAllAsPanelOpened(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->whereNull('panel_opened_at')
            ->update(['panel_opened_at' => now()]);
    }

    /**
     * Mark all notifications as fully read for a user.
     *
     * @param User $user The notification owner
     * @return int Number of notifications updated
     */
    public function markAllAsRead(User $user): int
    {
        $now = now();
        $baseQuery = function () use ($user) {
            return DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->where('tenant_id', $user->academy_id)
                ->whereNull('read_at');
        };

        // First, set panel_opened_at for notifications that don't have it
        $baseQuery()->whereNull('panel_opened_at')->update(['panel_opened_at' => $now]);

        // Then mark all as read
        return $baseQuery()->update(['read_at' => $now]);
    }

    /**
     * Delete a notification.
     *
     * @param string $notificationId The notification ID
     * @param User $user The notification owner
     * @return bool Whether the deletion was successful
     */
    public function delete(string $notificationId, User $user): bool
    {
        return DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->delete() > 0;
    }

    /**
     * Delete all read notifications older than X days.
     *
     * @param int $days Number of days
     * @return int Number of notifications deleted
     */
    public function deleteOldReadNotifications(int $days = 30): int
    {
        return DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Get unread notifications count for a user (not seen in panel yet).
     *
     * @param User $user The notification owner
     * @return int Unread count
     */
    public function getUnreadCount(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->whereNull('panel_opened_at')
            ->count();
    }

    /**
     * Get notifications for a user with pagination.
     *
     * @param User $user The notification owner
     * @param int $perPage Items per page
     * @param string|null $category Filter by category
     * @return LengthAwarePaginator
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $category = null): LengthAwarePaginator
    {
        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if a notification exists for the given criteria.
     *
     * @param User $user The notification owner
     * @param string $type Notification type
     * @param array $metadata Metadata to match
     * @param int $withinMinutes Only check within last X minutes
     * @return bool
     */
    public function existsRecent(User $user, string $type, array $metadata = [], int $withinMinutes = 5): bool
    {
        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('notification_type', $type)
            ->where('created_at', '>=', now()->subMinutes($withinMinutes));

        if (!empty($metadata)) {
            foreach ($metadata as $key => $value) {
                $query->where("metadata->{$key}", $value);
            }
        }

        return $query->exists();
    }
}
