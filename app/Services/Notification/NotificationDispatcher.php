<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Events\NotificationSent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Core notification dispatcher service.
 *
 * Handles sending notifications to users, persisting to database,
 * and broadcasting real-time updates via WebSockets.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly NotificationContentBuilder $contentBuilder
    ) {}

    /**
     * Send a notification to one or more users.
     *
     * @param User|Collection $users The user(s) to notify
     * @param NotificationType $type The notification type
     * @param array $data Data to interpolate into message
     * @param string|null $actionUrl URL when notification is clicked
     * @param array $metadata Additional metadata
     * @param bool $isImportant Mark as important
     * @param string|null $customIcon Override default icon
     * @param string|null $customColor Override default color
     * @return array Array of notification IDs created
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
    ): array {
        if ($users instanceof User) {
            $users = collect([$users]);
        }

        $notificationIds = [];

        foreach ($users as $user) {
            try {
                $id = $this->dispatchToUser(
                    $user,
                    $type,
                    $data,
                    $actionUrl,
                    $metadata,
                    $isImportant,
                    $customIcon,
                    $customColor
                );

                if ($id) {
                    $notificationIds[] = $id;
                }
            } catch (\Exception $e) {
                Log::error('Failed to send notification', [
                    'user_id' => $user->id,
                    'type' => $type->value,
                    'error' => $e->getMessage(),
                ]);
                report($e);
            }
        }

        return $notificationIds;
    }

    /**
     * Dispatch a notification to a single user.
     *
     * @return string|null The notification ID if successful
     */
    private function dispatchToUser(
        User $user,
        NotificationType $type,
        array $data,
        ?string $actionUrl,
        array $metadata,
        bool $isImportant,
        ?string $customIcon,
        ?string $customColor
    ): ?string {
        $category = $type->getCategory();
        $tenantId = $user->academy_id;

        // Use custom icon/color if provided, otherwise use category defaults
        $icon = $customIcon ?? $category->getIcon();
        $color = $customColor ?? $category->getColor();

        // Build notification content
        $title = $this->contentBuilder->getTitle($type, $data);
        $message = $this->contentBuilder->getMessage($type, $data);

        // Prepare notification data for display
        $displayData = array_merge($data, [
            'title' => $title,
            'message' => $message,
            'category' => $category->value,
            'icon' => $icon,
            'color' => $color,
        ]);

        // Prepare full notification payload
        $notificationPayload = [
            'type' => $type->value,
            'notification_type' => $type->value,
            'category' => $category->value,
            'icon' => $icon,
            'icon_color' => $color,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
            'is_important' => $isImportant,
            'tenant_id' => $tenantId,
            'data' => $displayData,
        ];

        // Persist to database
        $notificationId = $this->repository->create($user, $notificationPayload);

        // Broadcast real-time notification
        $this->broadcast($user, $notificationPayload);

        return $notificationId;
    }

    /**
     * Broadcast real-time notification to user.
     *
     * @param User $user The user to broadcast to
     * @param array $data The notification data
     */
    private function broadcast(User $user, array $data): void
    {
        try {
            broadcast(new NotificationSent($user, $data))->toOthers();
        } catch (\Exception $e) {
            Log::error('Failed to broadcast notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't report broadcast failures - notification was still saved
        }
    }

    /**
     * Check if a notification type is enabled for a user.
     *
     * @param User $user The user
     * @param NotificationType $type The notification type
     * @return bool
     */
    public function isNotificationEnabled(User $user, NotificationType $type): bool
    {
        // TODO: Implement user notification preferences
        // For now, all notifications are enabled
        return true;
    }
}
