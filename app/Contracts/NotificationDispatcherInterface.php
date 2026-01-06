<?php

namespace App\Contracts;

use App\Enums\NotificationType;
use App\Models\User;

/**
 * Interface for notification dispatch operations.
 *
 * Defines the contract for sending notifications to users
 * across different channels (database, broadcast, email).
 */
interface NotificationDispatcherInterface
{
    /**
     * Send a notification to a user.
     *
     * @param  User  $user  The user to notify
     * @param  NotificationType  $type  The notification type
     * @param  array  $data  Additional notification data
     * @param  string|null  $actionUrl  Optional action URL
     * @param  string|null  $actionText  Optional action button text
     * @return bool Whether the notification was sent successfully
     */
    public function send(
        User $user,
        NotificationType $type,
        array $data = [],
        ?string $actionUrl = null,
        ?string $actionText = null
    ): bool;

    /**
     * Send a notification to multiple users.
     *
     * @param  iterable  $users  Collection of users to notify
     * @param  NotificationType  $type  The notification type
     * @param  array  $data  Additional notification data
     * @param  string|null  $actionUrl  Optional action URL
     * @param  string|null  $actionText  Optional action button text
     * @return int Number of notifications sent
     */
    public function sendToMany(
        iterable $users,
        NotificationType $type,
        array $data = [],
        ?string $actionUrl = null,
        ?string $actionText = null
    ): int;

    /**
     * Mark a notification as read.
     *
     * @param  User  $user  The user
     * @param  string  $notificationId  The notification ID
     * @return bool Whether the notification was marked as read
     */
    public function markAsRead(User $user, string $notificationId): bool;

    /**
     * Mark all notifications as read for a user.
     *
     * @param  User  $user  The user
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(User $user): int;

    /**
     * Get unread notification count for a user.
     *
     * @param  User  $user  The user
     * @return int Unread count
     */
    public function getUnreadCount(User $user): int;
}
