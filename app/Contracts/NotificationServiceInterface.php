<?php

namespace App\Contracts;

use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notification\NotificationUrlBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Notification Service Interface
 *
 * Defines the contract for all notification operations in the system.
 * This service provides a unified API for sending, managing, and retrieving
 * notifications across different contexts (sessions, payments, subscriptions).
 *
 * The implementation delegates to specialized builders:
 * - SessionNotificationBuilder for session-related notifications
 * - PaymentNotificationBuilder for payment and payout notifications
 * - NotificationDispatcher for core sending logic
 * - NotificationRepository for database operations
 */
interface NotificationServiceInterface
{
    /**
     * Send a notification to one or more users.
     *
     * This is the main entry point for sending notifications with full customization.
     *
     * @param User|Collection $users Single user or collection of users to notify
     * @param NotificationType $type The notification type enum
     * @param array $data Data for notification content (merged with type's template)
     * @param string|null $actionUrl Optional URL for notification action button
     * @param array $metadata Optional metadata to store with notification
     * @param bool $isImportant Whether notification should be marked as important
     * @param string|null $customIcon Optional custom icon override
     * @param string|null $customColor Optional custom color override
     * @return void
     */
    public function send(
        User|Collection $users,
        NotificationType $type,
        array $data = [],
        ?string $actionUrl = null,
        array $metadata = [],
        bool $isImportant = false,
        ?string $customIcon = null,
        ?string $customColor = null
    ): void;

    /**
     * Mark a notification as read (clicked).
     *
     * Updates both read_at and panel_opened_at timestamps.
     *
     * @param string $notificationId The notification UUID
     * @param User $user The user who read the notification
     * @return bool True if notification was marked as read
     */
    public function markAsRead(string $notificationId, User $user): bool;

    /**
     * Mark all notifications as panel-opened (seen in panel but not clicked).
     *
     * This is typically called when user opens the notification panel.
     *
     * @param User $user The user
     * @return int Number of notifications updated
     */
    public function markAllAsPanelOpened(User $user): int;

    /**
     * Mark all notifications as fully read for a user.
     *
     * @param User $user The user
     * @return int Number of notifications updated
     */
    public function markAllAsRead(User $user): int;

    /**
     * Delete a notification.
     *
     * @param string $notificationId The notification UUID
     * @param User $user The user who owns the notification
     * @return bool True if notification was deleted
     */
    public function delete(string $notificationId, User $user): bool;

    /**
     * Delete all read notifications older than X days.
     *
     * Used for cleanup jobs to maintain database performance.
     *
     * @param int $days Age threshold in days (default: 30)
     * @return int Number of notifications deleted
     */
    public function deleteOldReadNotifications(int $days = 30): int;

    /**
     * Get unread notifications count for a user.
     *
     * Returns count of notifications that haven't been seen in the panel yet.
     *
     * @param User $user The user
     * @return int Number of unread notifications
     */
    public function getUnreadCount(User $user): int;

    /**
     * Get notifications for a user with pagination.
     *
     * @param User $user The user
     * @param int $perPage Number of notifications per page (default: 15)
     * @param string|null $category Optional category filter
     * @return LengthAwarePaginator Paginated notifications
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $category = null): LengthAwarePaginator;

    /**
     * Send session scheduled notification.
     *
     * Notifies student that a new session has been scheduled.
     *
     * @param Model $session The session model (QuranSession, AcademicSession, etc.)
     * @param User $student The student to notify
     * @return void
     */
    public function sendSessionScheduledNotification(Model $session, User $student): void;

    /**
     * Send session reminder notification.
     *
     * Sends reminder before session starts (typically 15-30 minutes before).
     *
     * @param Model $session The session model
     * @param User $student The student to notify
     * @return void
     */
    public function sendSessionReminderNotification(Model $session, User $student): void;

    /**
     * Send homework assigned notification.
     *
     * Notifies student when homework is assigned to a session.
     *
     * @param Model $session The session model
     * @param User $student The student to notify
     * @param int|null $homeworkId Optional homework ID for direct linking
     * @return void
     */
    public function sendHomeworkAssignedNotification(Model $session, User $student, ?int $homeworkId = null): void;

    /**
     * Send attendance marked notification.
     *
     * Notifies student/parent when attendance is recorded for a session.
     *
     * @param Model $attendance The attendance model
     * @param User $student The student to notify
     * @param string $status Attendance status (present, absent, late)
     * @return void
     */
    public function sendAttendanceMarkedNotification(Model $attendance, User $student, string $status): void;

    /**
     * Send payment success notification.
     *
     * Notifies user of successful payment completion.
     *
     * @param User $user The user to notify
     * @param array $paymentData Payment details (amount, subscription_code, etc.)
     * @return void
     */
    public function sendPaymentSuccessNotification(User $user, array $paymentData): void;

    /**
     * Send payment failed notification.
     *
     * Notifies user of failed payment with error details.
     *
     * @param User $user The user to notify
     * @param array $paymentData Payment details including error message
     * @return void
     */
    public function sendPaymentFailedNotification(User $user, array $paymentData): void;

    /**
     * Send payout approved notification to teacher.
     *
     * @param User $teacher The teacher to notify
     * @param array $payoutData Payout details (amount, date, etc.)
     * @return void
     */
    public function sendPayoutApprovedNotification(User $teacher, array $payoutData): void;

    /**
     * Send payout rejected notification to teacher.
     *
     * @param User $teacher The teacher to notify
     * @param array $payoutData Payout details including rejection reason
     * @return void
     */
    public function sendPayoutRejectedNotification(User $teacher, array $payoutData): void;

    /**
     * Send payout paid notification to teacher.
     *
     * @param User $teacher The teacher to notify
     * @param array $payoutData Payout details
     * @return void
     */
    public function sendPayoutPaidNotification(User $teacher, array $payoutData): void;

    /**
     * Send subscription renewal success notification.
     *
     * Notifies student when subscription is automatically renewed.
     *
     * @param User $student The student to notify
     * @param array $subscriptionData Subscription renewal details
     * @return void
     */
    public function sendSubscriptionRenewedNotification(User $student, array $subscriptionData): void;

    /**
     * Send subscription expiring reminder notification.
     *
     * Reminds student that subscription will expire soon.
     *
     * @param User $student The student to notify
     * @param array $subscriptionData Subscription details
     * @return void
     */
    public function sendSubscriptionExpiringNotification(User $student, array $subscriptionData): void;

    /**
     * Check if user has notification preferences enabled for a type.
     *
     * @param User $user The user
     * @param NotificationType $type The notification type
     * @return bool True if user has this notification type enabled
     */
    public function isNotificationEnabled(User $user, NotificationType $type): bool;

    /**
     * Get the URL builder for custom URL generation.
     *
     * @return NotificationUrlBuilder The URL builder instance
     */
    public function getUrlBuilder(): NotificationUrlBuilder;
}
