<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CriticalAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public readonly string $severity,
        public readonly string $source,
        public readonly string $body,
    ) {
        $this->onQueue(config('telegram.queue', 'notifications'));
    }

    public function via(mixed $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(mixed $notifiable): array
    {
        $severity = strtolower($this->severity);
        $emoji = match ($severity) {
            'crit', 'critical' => "\u{1F6A8}",
            'medium' => "\u{26A0}\u{FE0F}",
            default => "\u{2139}\u{FE0F}",
        };
        $tag = match ($severity) {
            'crit', 'critical' => '[CRIT]',
            'medium' => '[MED]',
            default => '[INFO]',
        };

        $host = gethostname() ?: 'unknown';
        $timestamp = gmdate('Y-m-d H:i:s').' UTC';

        $text = "{$emoji} {$tag} {$this->source} @ {$host}\n"
            ."{$timestamp}\n\n"
            .$this->body;

        return [
            'text' => $text,
            'severity' => $severity === 'critical' ? 'crit' : $severity,
            'source' => $this->source,
            'disable_web_page_preview' => true,
        ];
    }
}
