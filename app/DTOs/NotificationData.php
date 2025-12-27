<?php

namespace App\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for Notification Payloads
 *
 * Represents a notification with type, content, and delivery metadata.
 * Supports multiple channels (database, push, email, SMS).
 */
class NotificationData
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
        public readonly ?string $icon = null,
        public readonly array $data = [],
        public readonly array $channels = ['database'],
        public readonly ?int $userId = null,
        public readonly ?int $academyId = null,
        public readonly ?Carbon $scheduledAt = null,
        public readonly string $priority = 'normal',
    ) {}

    /**
     * Create a session reminder notification
     */
    public static function sessionReminder(
        int $userId,
        string $sessionType,
        string $sessionTitle,
        Carbon $sessionTime,
        ?string $actionUrl = null
    ): self {
        return new self(
            type: 'session_reminder',
            title: 'تذكير بالجلسة',
            body: sprintf('لديك جلسة "%s" في %s', $sessionTitle, $sessionTime->translatedFormat('H:i')),
            actionUrl: $actionUrl,
            actionLabel: 'عرض الجلسة',
            icon: 'calendar',
            data: [
                'session_type' => $sessionType,
                'session_time' => $sessionTime->toDateTimeString(),
            ],
            channels: ['database', 'push'],
            userId: $userId,
            priority: 'high',
        );
    }

    /**
     * Create a payment notification
     */
    public static function paymentReceived(
        int $userId,
        float $amount,
        string $subscriptionName,
        ?string $actionUrl = null
    ): self {
        return new self(
            type: 'payment_received',
            title: 'تم استلام الدفعة',
            body: sprintf('تم استلام دفعة بقيمة %.2f ر.س لـ %s', $amount, $subscriptionName),
            actionUrl: $actionUrl,
            actionLabel: 'عرض الإيصال',
            icon: 'credit-card',
            data: [
                'amount' => $amount,
                'subscription_name' => $subscriptionName,
            ],
            channels: ['database', 'email'],
            userId: $userId,
        );
    }

    /**
     * Create a homework notification
     */
    public static function homeworkAssigned(
        int $userId,
        string $teacherName,
        string $homeworkTitle,
        ?Carbon $dueDate = null,
        ?string $actionUrl = null
    ): self {
        $body = sprintf('قام %s بتكليفك بواجب: %s', $teacherName, $homeworkTitle);
        if ($dueDate) {
            $body .= sprintf(' - موعد التسليم: %s', $dueDate->translatedFormat('d M Y'));
        }

        return new self(
            type: 'homework_assigned',
            title: 'واجب جديد',
            body: $body,
            actionUrl: $actionUrl,
            actionLabel: 'عرض الواجب',
            icon: 'book',
            data: [
                'teacher_name' => $teacherName,
                'homework_title' => $homeworkTitle,
                'due_date' => $dueDate?->toDateString(),
            ],
            channels: ['database', 'push'],
            userId: $userId,
        );
    }

    /**
     * Create a certificate issued notification
     */
    public static function certificateIssued(
        int $userId,
        string $certificateName,
        ?string $downloadUrl = null
    ): self {
        return new self(
            type: 'certificate_issued',
            title: 'شهادة جديدة',
            body: sprintf('تهانينا! تم إصدار شهادة "%s" لك', $certificateName),
            actionUrl: $downloadUrl,
            actionLabel: 'تحميل الشهادة',
            icon: 'award',
            data: [
                'certificate_name' => $certificateName,
            ],
            channels: ['database', 'push', 'email'],
            userId: $userId,
            priority: 'normal',
        );
    }

    /**
     * Check if notification should be sent immediately
     */
    public function isImmediate(): bool
    {
        return $this->scheduledAt === null;
    }

    /**
     * Check if notification is high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority === 'high';
    }

    /**
     * Get channels as array
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Check if should send via specific channel
     */
    public function shouldSendVia(string $channel): bool
    {
        return in_array($channel, $this->channels);
    }

    /**
     * Convert to database notification array
     */
    public function toDatabaseArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'icon' => $this->icon,
            'data' => $this->data,
            'priority' => $this->priority,
        ];
    }

    /**
     * Convert to push notification array
     */
    public function toPushArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'click_action' => $this->actionUrl,
            'data' => array_merge($this->data, [
                'type' => $this->type,
            ]),
        ];
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'icon' => $this->icon,
            'data' => $this->data,
            'channels' => $this->channels,
            'user_id' => $this->userId,
            'academy_id' => $this->academyId,
            'scheduled_at' => $this->scheduledAt?->toDateTimeString(),
            'priority' => $this->priority,
        ];
    }
}
