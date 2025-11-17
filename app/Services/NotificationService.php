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
        bool $isImportant = false
    ): void {
        if ($users instanceof User) {
            $users = collect([$users]);
        }

        foreach ($users as $user) {
            try {
                $this->createNotification($user, $type, $data, $actionUrl, $metadata, $isImportant);
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
        bool $isImportant
    ): void {
        $category = $type->getCategory();
        $tenantId = $user->academy_id;

        // Prepare notification data
        $notificationData = [
            'type' => $type->value,
            'notification_type' => $type->value,
            'category' => $category->value,
            'icon' => $category->getIcon(),
            'icon_color' => $category->getColor(),
            'action_url' => $actionUrl,
            'metadata' => $metadata,
            'is_important' => $isImportant,
            'tenant_id' => $tenantId,
            'data' => array_merge($data, [
                'title' => $this->getTitle($type, $data),
                'message' => $this->getMessage($type, $data),
                'category' => $category->value,
                'icon' => $category->getIcon(),
                'color' => $category->getColor(),
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
            'icon' => $category->getIcon(),
            'icon_color' => $category->getColor(),
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
     * Mark notification as read
     */
    public function markAsRead(string $notificationId, User $user): bool
    {
        return DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->update(['read_at' => now()]) > 0;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
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
     * Get unread notifications count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get notifications for a user with pagination
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $category = null)
    {
        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user));

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
    public function sendHomeworkAssignedNotification(Model $session, User $student, array $homeworkData): void
    {
        $sessionType = class_basename($session);

        $this->send(
            $student,
            NotificationType::HOMEWORK_ASSIGNED,
            [
                'session_title' => $session->title ?? $sessionType,
                'teacher_name' => $session->teacher?->full_name ?? '',
                'due_date' => $homeworkData['due_date'] ?? '',
            ],
            $this->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'homework_data' => $homeworkData,
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
        $this->send(
            $user,
            NotificationType::PAYMENT_SUCCESS,
            [
                'amount' => $paymentData['amount'] ?? 0,
                'currency' => $paymentData['currency'] ?? 'SAR',
                'description' => $paymentData['description'] ?? '',
            ],
            '/student/payments',
            [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
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
                'quransession' => "/student/session-detail/{$sessionId}",
                'academicsession' => "/student/academic-session-detail/{$sessionId}",
                'interactivesession' => "/student/interactive-course-detail/{$session->course_id}",
                default => "/student/sessions",
            };
        } elseif ($user->hasRole(['quran_teacher', 'academic_teacher'])) {
            return "/teacher/session-detail/{$sessionId}";
        }

        return '/';
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