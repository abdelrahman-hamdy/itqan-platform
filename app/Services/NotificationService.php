<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to one or more users
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
        if ($users instanceof User) {
            $users = collect([$users]);
        }

        foreach ($users as $user) {
            try {
                $this->createNotification($user, $type, $data, $actionUrl, $metadata, $isImportant, $customIcon, $customColor);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', [
                    'user_id' => $user->id,
                    'type' => $type->value,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create a notification for a user
     */
    protected function createNotification(
        User $user,
        NotificationType $type,
        array $data,
        ?string $actionUrl,
        array $metadata,
        bool $isImportant,
        ?string $customIcon = null,
        ?string $customColor = null
    ): void {
        $category = $type->getCategory();
        $tenantId = $user->academy_id;

        // Use custom icon/color if provided, otherwise use category defaults
        $icon = $customIcon ?? $category->getIcon();
        $color = $customColor ?? $category->getColor();

        // Prepare notification data
        $notificationData = [
            'type' => $type->value,
            'notification_type' => $type->value,
            'category' => $category->value,
            'icon' => $icon,
            'icon_color' => $color,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
            'is_important' => $isImportant,
            'tenant_id' => $tenantId,
            'data' => array_merge($data, [
                'title' => $this->getTitle($type, $data),
                'message' => $this->getMessage($type, $data),
                'category' => $category->value,
                'icon' => $icon,
                'color' => $color,
            ])
        ];

        // Create database notification
        DB::table('notifications')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => get_class($this) . '\\' . $type->value,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => json_encode($notificationData['data']),
            'notification_type' => $type->value,
            'category' => $category->value,
            'icon' => $icon,
            'icon_color' => $color,
            'action_url' => $actionUrl,
            'metadata' => json_encode($metadata),
            'is_important' => $isImportant,
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Broadcast real-time notification if enabled
        $this->broadcastNotification($user, $notificationData);
    }

    /**
     * Get notification title based on type and data
     */
    protected function getTitle(NotificationType $type, array $data): string
    {
        return __($type->getTitleKey(), $data);
    }

    /**
     * Get notification message based on type and data
     */
    protected function getMessage(NotificationType $type, array $data): string
    {
        return __($type->getMessageKey(), $data);
    }

    /**
     * Broadcast real-time notification to user
     */
    protected function broadcastNotification(User $user, array $data): void
    {
        try {
            // Broadcast using Laravel Echo
            broadcast(new \App\Events\NotificationSent($user, $data))->toOthers();
        } catch (\Exception $e) {
            Log::error('Failed to broadcast notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark notification as read (clicked)
     */
    public function markAsRead(string $notificationId, User $user): bool
    {
        return DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->update([
                'read_at' => now(),
                'panel_opened_at' => DB::raw('COALESCE(panel_opened_at, NOW())'),
            ]) > 0;
    }

    /**
     * Mark all notifications as panel-opened (seen in panel but not clicked)
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
     * Mark all notifications as fully read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'panel_opened_at' => DB::raw('COALESCE(panel_opened_at, NOW())'),
            ]);
    }

    /**
     * Delete a notification
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
     * Delete all read notifications older than X days
     */
    public function deleteOldReadNotifications(int $days = 30): int
    {
        return DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Get unread notifications count for a user (not seen in panel yet)
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
     * Get notifications for a user with pagination
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $category = null)
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
     * Send session scheduled notification
     */
    public function sendSessionScheduledNotification(Model $session, User $student): void
    {
        $sessionType = class_basename($session);
        $teacherName = $session->teacher?->full_name ?? '';

        $this->send(
            $student,
            NotificationType::SESSION_SCHEDULED,
            [
                'session_title' => $session->title ?? $sessionType,
                'teacher_name' => $teacherName,
                'start_time' => $session->start_time->format('Y-m-d H:i'),
                'session_type' => $sessionType,
            ],
            $this->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
            ],
            true
        );
    }

    /**
     * Send session reminder notification
     */
    public function sendSessionReminderNotification(Model $session, User $student): void
    {
        $sessionType = class_basename($session);

        $this->send(
            $student,
            NotificationType::SESSION_REMINDER,
            [
                'session_title' => $session->title ?? $sessionType,
                'minutes' => 30,
                'start_time' => $session->start_time->format('H:i'),
            ],
            $this->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
            ],
            true
        );
    }

    /**
     * Send homework assigned notification
     */
    public function sendHomeworkAssignedNotification(Model $session, User $student, ?int $homeworkId = null): void
    {
        $sessionType = class_basename($session);

        // If homework ID is provided, link to specific homework view
        // Otherwise, link to session page
        $actionUrl = $homeworkId
            ? "/homework/{$homeworkId}/view"
            : $this->getSessionUrl($session, $student);

        $this->send(
            $student,
            NotificationType::HOMEWORK_ASSIGNED,
            [
                'session_title' => $session->title ?? $sessionType,
                'teacher_name' => $session->teacher?->full_name ?? '',
                'due_date' => $session->homework_due_date?->format('Y-m-d') ?? '',
            ],
            $actionUrl,
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'homework_id' => $homeworkId,
            ]
        );
    }

    /**
     * Send attendance marked notification
     */
    public function sendAttendanceMarkedNotification(Model $attendance, User $student, string $status): void
    {
        $session = $attendance->attendanceable;
        $type = match($status) {
            'present' => NotificationType::ATTENDANCE_MARKED_PRESENT,
            'absent' => NotificationType::ATTENDANCE_MARKED_ABSENT,
            'late' => NotificationType::ATTENDANCE_MARKED_LATE,
            default => NotificationType::ATTENDANCE_MARKED_PRESENT,
        };

        $this->send(
            $student,
            $type,
            [
                'session_title' => $session->title ?? class_basename($session),
                'date' => $session->start_time->format('Y-m-d'),
            ],
            $this->getSessionUrl($session, $student),
            [
                'attendance_id' => $attendance->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'status' => $status,
            ]
        );
    }

    /**
     * Send payment success notification
     */
    public function sendPaymentSuccessNotification(User $user, array $paymentData): void
    {
        // Determine the best URL based on what's available
        $actionUrl = '/payments';

        if (isset($paymentData['subscription_id']) && isset($paymentData['subscription_type'])) {
            // Link to specific subscription page
            $actionUrl = match($paymentData['subscription_type']) {
                'quran' => "/circles/{$paymentData['circle_id']}",
                'academic' => "/academic-subscriptions/{$paymentData['subscription_id']}",
                default => '/subscriptions',
            };
        }

        $this->send(
            $user,
            NotificationType::PAYMENT_SUCCESS,
            [
                'amount' => $paymentData['amount'] ?? 0,
                'currency' => $paymentData['currency'] ?? 'SAR',
                'description' => $paymentData['description'] ?? '',
            ],
            $actionUrl,
            [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'subscription_id' => $paymentData['subscription_id'] ?? null,
                'subscription_type' => $paymentData['subscription_type'] ?? null,
            ],
            true
        );
    }

    /**
     * Get the appropriate URL for a session based on user role
     */
    protected function getSessionUrl(Model $session, User $user): string
    {
        $sessionType = strtolower(class_basename($session));
        $sessionId = $session->id;

        if ($user->hasRole(['student'])) {
            return match($sessionType) {
                'quransession' => "/sessions/{$sessionId}",
                'academicsession' => "/academic-sessions/{$sessionId}",
                'interactivecoursesession' => "/student/interactive-sessions/{$sessionId}",
                default => "/sessions/{$sessionId}",
            };
        } elseif ($user->hasRole(['quran_teacher'])) {
            return $this->getTeacherCircleUrl($session);
        } elseif ($user->hasRole(['academic_teacher'])) {
            return "/teacher/academic-sessions/{$sessionId}";
        }

        return '/';
    }

    /**
     * Get circle URL from session for students
     */
    protected function getCircleUrlFromSession(Model $session): string
    {
        // For Quran sessions that belong to a circle
        if (method_exists($session, 'circle') && $session->circle) {
            return "/circles/{$session->circle->id}";
        }

        return "/sessions/{$session->id}";
    }

    /**
     * Get appropriate teacher URL based on circle type
     */
    protected function getTeacherCircleUrl(Model $session): string
    {
        if (method_exists($session, 'circle') && $session->circle) {
            $circle = $session->circle;

            if ($circle->circle_type === 'individual') {
                return "/teacher/individual-circles/{$circle->id}";
            } elseif ($circle->circle_type === 'group') {
                return "/teacher/group-circles/{$circle->id}";
            }
        }

        return "/teacher/sessions/{$session->id}";
    }

    /**
     * Check if user has notification preferences enabled for a type
     */
    public function isNotificationEnabled(User $user, NotificationType $type): bool
    {
        // TODO: Implement user notification preferences
        // For now, all notifications are enabled
        return true;
    }
}