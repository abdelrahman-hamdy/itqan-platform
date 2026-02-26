<?php

namespace App\Services\Notification;

use Exception;
use App\Enums\NotificationType;
use App\Events\NotificationSent;
use App\Models\Academy;
use App\Models\User;
use App\Notifications\GenericEmailNotification;
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
     * @param  User|Collection  $users  The user(s) to notify
     * @param  NotificationType  $type  The notification type
     * @param  array  $data  Data to interpolate into message
     * @param  string|null  $actionUrl  URL when notification is clicked
     * @param  array  $metadata  Additional metadata
     * @param  bool  $isImportant  Mark as important
     * @param  string|null  $customIcon  Override default icon
     * @param  string|null  $customColor  Override default color
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
            } catch (Exception $e) {
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

        // Use custom icon/color if provided, otherwise use type-specific (which falls back to category)
        $icon = $customIcon ?? $type->getIcon();
        $color = $customColor ?? $type->getTailwindColor();

        // Build notification content
        $title = $this->contentBuilder->getTitle($type, $data);
        $message = $this->contentBuilder->getMessage($type, $data);

        // Build the full display data for broadcasting (can include full $data payload for FCM delivery)
        $displayData = array_merge($data, [
            // Frontend Livewire NotificationCenter reads these
            'title' => $title,
            'message' => $message,
            'category' => $category->value,
            'icon' => $icon,
            'color' => $color,

            // Filament database notifications read these
            'body' => $message,
            'iconColor' => $type->getFilamentColor(),
            'actions' => $actionUrl ? [
                [
                    'name' => 'view',
                    'label' => __('notifications.actions.view'),
                    'url' => $actionUrl,
                    'color' => 'primary',
                    'shouldOpenUrlInNewTab' => false,
                    'shouldMarkAsRead' => true,
                    'view' => 'filament-actions::button-action',
                ],
            ] : [],
            'format' => 'filament',
            'duration' => 'persistent',
        ]);

        // Build minimal payload for DB storage — only display-safe fields, no raw $data blob.
        // This prevents leaking internal model IDs, payment tokens, or other sensitive context
        // that is only needed for FCM delivery but not for rendering stored notifications.
        // TODO: Add a scheduled command to purge notifications older than 90 days:
        // Notification::where('created_at', '<', now()->subDays(90))->delete();
        $minimalStorageData = [
            'title' => $title,
            'message' => $message,
            'category' => $category->value,
            'icon' => $icon,
            'color' => $color,
            'action_url' => $actionUrl ?? null,

            // Filament database notifications read these fields
            'body' => $message,
            'iconColor' => $type->getFilamentColor(),
            'actions' => $displayData['actions'],
            'format' => 'filament',
            'duration' => 'persistent',
        ];

        // DB payload uses minimal storage data; broadcast payload uses full display data for push delivery
        $dbNotificationPayload = [
            'type' => $type->value,
            'notification_type' => $type->value,
            'category' => $category->value,
            'icon' => $icon,
            'icon_color' => $color,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
            'is_important' => $isImportant,
            'tenant_id' => $tenantId,
            'data' => $minimalStorageData,
        ];

        $broadcastPayload = [
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

        // Save minimal data to database; broadcast full display data for real-time updates
        $notificationId = $this->repository->create($user, $dbNotificationPayload);
        $this->broadcast($user, $broadcastPayload);

        // Send email if academy settings allow
        $this->sendEmailIfEnabled($user, $type, $title, $message, $actionUrl);

        return $notificationId;
    }

    /**
     * Broadcast real-time notification to user.
     *
     * @param  User  $user  The user to broadcast to
     * @param  array  $data  The notification data
     */
    private function broadcast(User $user, array $data): void
    {
        try {
            broadcast(new NotificationSent($user, $data))->toOthers();
        } catch (Exception $e) {
            Log::error('Failed to broadcast notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't report broadcast failures - notification was still saved
        }
    }

    /**
     * Send email notification if enabled for the user's academy and notification category.
     */
    private function sendEmailIfEnabled(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        ?string $actionUrl
    ): void {
        try {
            if (empty($user->email)) {
                return;
            }

            if (! $user->academy_id) {
                return;
            }

            $academy = Academy::find($user->academy_id);

            if (! $academy) {
                return;
            }

            $category = $type->getCategory()->value;

            if (! $academy->isEmailEnabledForCategory($category)) {
                return;
            }

            $user->notify(new GenericEmailNotification(
                title: $title,
                message: $message,
                actionUrl: $actionUrl,
                academy: $academy
            ));
        } catch (Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'type' => $type->value,
                'error' => $e->getMessage(),
            ]);
            // Don't re-throw - email failure should not break database notifications
        }
    }

    /**
     * Check if a notification type is enabled for a user.
     *
     * @param  User  $user  The user
     * @param  NotificationType  $type  The notification type
     */
    /**
     * Check if a notification type is enabled for a user.
     * Always returns true since user preferences have been removed.
     * All notifications are now enabled by default (academy settings control email only).
     */
    public function isNotificationEnabled(User $user, NotificationType $type): bool
    {
        return true;
    }
}
