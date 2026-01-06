<?php

namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Notification\NotificationRepository;
use App\Services\Notification\NotificationUrlBuilder;
use App\Services\Notification\PaymentNotificationBuilder;
use App\Services\Notification\SessionNotificationBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Main notification service facade.
 *
 * This class provides a unified API for all notification operations,
 * delegating to specialized services internally for a cleaner architecture.
 *
 * @see NotificationDispatcher For core sending logic
 * @see NotificationRepository For database operations
 * @see SessionNotificationBuilder For session notifications
 * @see PaymentNotificationBuilder For payment notifications
 */
class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
        private readonly NotificationRepository $repository,
        private readonly SessionNotificationBuilder $sessionNotificationBuilder,
        private readonly PaymentNotificationBuilder $paymentNotificationBuilder,
        private readonly NotificationUrlBuilder $urlBuilder
    ) {}

    // ========================================
    // CORE NOTIFICATION OPERATIONS
    // ========================================

    /**
     * Send a notification to one or more users.
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
    ): void {
        $this->dispatcher->send(
            $users,
            $type,
            $data,
            $actionUrl,
            $metadata,
            $isImportant,
            $customIcon,
            $customColor
        );
    }

    // ========================================
    // NOTIFICATION MANAGEMENT
    // ========================================

    /**
     * Mark notification as read (clicked).
     */
    public function markAsRead(string $notificationId, User $user): bool
    {
        return $this->repository->markAsRead($notificationId, $user);
    }

    /**
     * Mark all notifications as panel-opened (seen in panel but not clicked).
     */
    public function markAllAsPanelOpened(User $user): int
    {
        return $this->repository->markAllAsPanelOpened($user);
    }

    /**
     * Mark all notifications as fully read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return $this->repository->markAllAsRead($user);
    }

    /**
     * Delete a notification.
     */
    public function delete(string $notificationId, User $user): bool
    {
        return $this->repository->delete($notificationId, $user);
    }

    /**
     * Delete all read notifications older than X days.
     */
    public function deleteOldReadNotifications(int $days = 30): int
    {
        return $this->repository->deleteOldReadNotifications($days);
    }

    /**
     * Get unread notifications count for a user (not seen in panel yet).
     */
    public function getUnreadCount(User $user): int
    {
        return $this->repository->getUnreadCount($user);
    }

    /**
     * Get notifications for a user with pagination.
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $category = null): LengthAwarePaginator
    {
        return $this->repository->getNotifications($user, $perPage, $category);
    }

    // ========================================
    // SESSION NOTIFICATIONS
    // ========================================

    /**
     * Send session scheduled notification.
     */
    public function sendSessionScheduledNotification(Model $session, User $student): void
    {
        $this->sessionNotificationBuilder->sendSessionScheduledNotification($session, $student);
    }

    /**
     * Send session reminder notification.
     */
    public function sendSessionReminderNotification(Model $session, User $student): void
    {
        $this->sessionNotificationBuilder->sendSessionReminderNotification($session, $student);
    }

    /**
     * Send homework assigned notification.
     */
    public function sendHomeworkAssignedNotification(Model $session, User $student, ?int $homeworkId = null): void
    {
        $this->sessionNotificationBuilder->sendHomeworkAssignedNotification($session, $student, $homeworkId);
    }

    /**
     * Send attendance marked notification.
     */
    public function sendAttendanceMarkedNotification(Model $attendance, User $student, string $status): void
    {
        $this->sessionNotificationBuilder->sendAttendanceMarkedNotification($attendance, $student, $status);
    }

    // ========================================
    // PAYMENT NOTIFICATIONS
    // ========================================

    /**
     * Send payment success notification.
     */
    public function sendPaymentSuccessNotification(User $user, array $paymentData): void
    {
        $this->paymentNotificationBuilder->sendPaymentSuccessNotification($user, $paymentData);
    }

    /**
     * Send payment failed notification.
     */
    public function sendPaymentFailedNotification(User $user, array $paymentData): void
    {
        $this->paymentNotificationBuilder->sendPaymentFailedNotification($user, $paymentData);
    }

    // ========================================
    // TEACHER PAYOUT NOTIFICATIONS
    // ========================================

    /**
     * Send payout approved notification to teacher.
     */
    public function sendPayoutApprovedNotification(User $teacher, array $payoutData): void
    {
        $this->paymentNotificationBuilder->sendPayoutApprovedNotification($teacher, $payoutData);
    }

    /**
     * Send payout rejected notification to teacher.
     */
    public function sendPayoutRejectedNotification(User $teacher, array $payoutData): void
    {
        $this->paymentNotificationBuilder->sendPayoutRejectedNotification($teacher, $payoutData);
    }

    /**
     * Send payout paid notification to teacher.
     */
    public function sendPayoutPaidNotification(User $teacher, array $payoutData): void
    {
        $this->paymentNotificationBuilder->sendPayoutPaidNotification($teacher, $payoutData);
    }

    // ========================================
    // SUBSCRIPTION RENEWAL NOTIFICATIONS
    // ========================================

    /**
     * Send subscription renewal success notification.
     */
    public function sendSubscriptionRenewedNotification(User $student, array $subscriptionData): void
    {
        $this->paymentNotificationBuilder->sendSubscriptionRenewedNotification($student, $subscriptionData);
    }

    /**
     * Send subscription expiring reminder notification.
     */
    public function sendSubscriptionExpiringNotification(User $student, array $subscriptionData): void
    {
        $this->paymentNotificationBuilder->sendSubscriptionExpiringNotification($student, $subscriptionData);
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Check if user has notification preferences enabled for a type.
     */
    public function isNotificationEnabled(User $user, NotificationType $type): bool
    {
        return $this->dispatcher->isNotificationEnabled($user, $type);
    }

    /**
     * Get the URL builder for custom URL generation.
     */
    public function getUrlBuilder(): NotificationUrlBuilder
    {
        return $this->urlBuilder;
    }
}
