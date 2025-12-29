<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for notification payloads.
 *
 * Used by NotificationService to structure notification data for
 * database storage, broadcasting, and display across different channels.
 *
 * @property-read string $type Notification type identifier
 * @property-read string $title Notification title/heading
 * @property-read string $body Notification message body
 * @property-read string|null $actionUrl URL to navigate to when clicked
 * @property-read string|null $icon Icon identifier or CSS class
 * @property-read string|null $color Display color (hex or CSS color)
 * @property-read array $metadata Additional notification metadata
 * @property-read bool $isImportant Whether notification requires immediate attention
 */
readonly class NotificationPayload
{
    public function __construct(
        public string $type,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $icon = null,
        public ?string $color = null,
        public array $metadata = [],
        public bool $isImportant = false,
    ) {}

    /**
     * Create a session notification.
     */
    public static function forSession(
        string $title,
        string $body,
        string $sessionType,
        ?string $actionUrl = null,
        bool $isImportant = false
    ): self {
        $icons = [
            'quran' => 'heroicon-o-book-open',
            'academic' => 'heroicon-o-academic-cap',
            'interactive' => 'heroicon-o-users',
        ];

        $colors = [
            'quran' => '#10b981',
            'academic' => '#3b82f6',
            'interactive' => '#8b5cf6',
        ];

        return new self(
            type: "session.{$sessionType}",
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            icon: $icons[$sessionType] ?? 'heroicon-o-bell',
            color: $colors[$sessionType] ?? '#6b7280',
            metadata: ['session_type' => $sessionType],
            isImportant: $isImportant,
        );
    }

    /**
     * Create a payment notification.
     */
    public static function forPayment(
        string $title,
        string $body,
        bool $isSuccess,
        ?string $actionUrl = null
    ): self {
        return new self(
            type: 'payment.' . ($isSuccess ? 'success' : 'failed'),
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            icon: $isSuccess ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
            color: $isSuccess ? '#10b981' : '#ef4444',
            metadata: ['payment_status' => $isSuccess ? 'success' : 'failed'],
            isImportant: !$isSuccess,
        );
    }

    /**
     * Create a homework notification.
     */
    public static function forHomework(
        string $title,
        string $body,
        string $homeworkType,
        ?string $actionUrl = null,
        bool $isImportant = false
    ): self {
        return new self(
            type: "homework.{$homeworkType}",
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            icon: 'heroicon-o-clipboard-document-list',
            color: '#f59e0b',
            metadata: ['homework_type' => $homeworkType],
            isImportant: $isImportant,
        );
    }

    /**
     * Create instance from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            title: $data['title'],
            body: $data['body'],
            actionUrl: $data['actionUrl'] ?? $data['action_url'] ?? null,
            icon: $data['icon'] ?? null,
            color: $data['color'] ?? null,
            metadata: $data['metadata'] ?? [],
            isImportant: (bool) ($data['isImportant'] ?? $data['is_important'] ?? false),
        );
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'icon' => $this->icon,
            'color' => $this->color,
            'metadata' => $this->metadata,
            'is_important' => $this->isImportant,
        ];
    }

    /**
     * Convert to database notification data format.
     */
    public function toDatabaseNotification(): array
    {
        return [
            'type' => $this->type,
            'data' => [
                'title' => $this->title,
                'body' => $this->body,
                'action_url' => $this->actionUrl,
                'icon' => $this->icon,
                'color' => $this->color,
                'metadata' => $this->metadata,
            ],
        ];
    }

    /**
     * Convert to broadcast notification format.
     */
    public function toBroadcastPayload(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'action_url' => $this->actionUrl,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_important' => $this->isImportant,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get notification priority level.
     */
    public function getPriority(): string
    {
        return $this->isImportant ? 'high' : 'normal';
    }
}
