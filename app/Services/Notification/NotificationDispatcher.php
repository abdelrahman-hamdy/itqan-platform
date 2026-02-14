<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Events\NotificationSent;
use App\Models\Academy;
use App\Models\User;
use App\Models\UserNotificationPreference;
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

        // Use custom icon/color if provided, otherwise use type-specific (which falls back to category)
        $icon = $customIcon ?? $type->getIcon();
        $color = $customColor ?? $type->getTailwindColor();

        // Build notification content
        $title = $this->contentBuilder->getTitle($type, $data);
        $message = $this->contentBuilder->getMessage($type, $data);

        // Prepare notification data for display
        // Includes both frontend fields (title, message, color) and Filament fields (body, iconColor, actions, format)
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

        // Check user preferences for database channel
        $databaseEnabled = UserNotificationPreference::isChannelEnabled($user->id, $category, 'database');
        $notificationId = null;

        if ($databaseEnabled) {
            // Persist to database
            $notificationId = $this->repository->create($user, $notificationPayload);

            // Broadcast real-time notification
            $this->broadcast($user, $notificationPayload);
        }

        // Check user preferences for email channel
        $emailEnabled = UserNotificationPreference::isChannelEnabled($user->id, $category, 'email');
        if ($emailEnabled) {
            $this->sendEmailIfEnabled($user, $type, $title, $message, $actionUrl);
        }

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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
     * Checks user preferences for the database channel.
     */
    public function isNotificationEnabled(User $user, NotificationType $type): bool
    {
        return UserNotificationPreference::isChannelEnabled(
            $user->id,
            $type->getCategory(),
            'database'
        );
    }
}
