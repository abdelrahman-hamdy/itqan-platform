<?php

namespace App\Contracts;

use App\DTOs\NotificationData;
use App\Models\User;

/**
 * Interface for notification sending services.
 *
 * This interface defines the contract for sending notifications across
 * multiple channels (database, push, email, SMS).
 */
interface NotificationSenderInterface
{
    /**
     * Send a notification to a user.
     *
     * @param User $user The recipient of the notification
     * @param NotificationData $notification The notification data to send
     * @return bool Whether the notification was sent successfully
     */
    public function send(User $user, NotificationData $notification): bool;

    /**
     * Send a notification to multiple users.
     *
     * @param array<User> $users The recipients of the notification
     * @param NotificationData $notification The notification data to send
     * @return int Number of successful sends
     */
    public function sendToMany(array $users, NotificationData $notification): int;

    /**
     * Send a session reminder notification.
     *
     * @param User $user The recipient
     * @param mixed $session The session model (QuranSession, AcademicSession, etc.)
     * @return bool Whether the notification was sent successfully
     */
    public function sendSessionReminder(User $user, mixed $session): bool;

    /**
     * Send a homework assigned notification.
     *
     * @param mixed $session The session model
     * @param User $student The student who received the homework
     * @param int|null $homeworkId Optional homework ID
     * @return bool Whether the notification was sent successfully
     */
    public function sendHomeworkAssignedNotification(mixed $session, User $student, ?int $homeworkId = null): bool;

    /**
     * Send a payment confirmation notification.
     *
     * @param User $user The user who made the payment
     * @param mixed $payment The payment model
     * @return bool Whether the notification was sent successfully
     */
    public function sendPaymentConfirmation(User $user, mixed $payment): bool;

    /**
     * Mark all notifications as read for a user.
     *
     * @param User $user The user
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(User $user): int;
}
