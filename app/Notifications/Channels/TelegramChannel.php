<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! config('telegram.enabled')) {
            return;
        }

        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        /** @var array{text?: string, severity?: string, source?: string, parse_mode?: string, disable_web_page_preview?: bool, chat_id?: string|int} $payload */
        $payload = call_user_func([$notification, 'toTelegram'], $notifiable);

        if (! is_array($payload) || empty($payload['text'])) {
            return;
        }

        $chatId = $payload['chat_id']
            ?? $this->resolveChatId($notifiable)
            ?? config('telegram.chat_id');

        if (empty($chatId)) {
            Log::warning('TelegramChannel: no chat_id resolved — dropping alert', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        if (! $this->shouldDeliver($payload)) {
            return;
        }

        $token = config('telegram.bot_token');

        if (empty($token)) {
            Log::warning('TelegramChannel: bot_token missing — dropping alert');

            return;
        }

        $base = rtrim((string) config('telegram.api_base', 'https://api.telegram.org'), '/');
        $url = "{$base}/bot{$token}/sendMessage";

        $body = [
            'chat_id' => $chatId,
            'text' => $payload['text'],
        ];

        if (! empty($payload['parse_mode'])) {
            $body['parse_mode'] = $payload['parse_mode'];
        }

        if (! empty($payload['disable_web_page_preview'])) {
            $body['disable_web_page_preview'] = true;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('telegram.request_timeout', 8))
                ->post($url, $body);

            if (! $response->successful()) {
                Log::warning('TelegramChannel: send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('TelegramChannel: HTTP error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rate-limit per (severity, source) tuple — mirrors the bash
     * dispatcher's 5-minute lockfile behavior so the Telegram side and the
     * shell side can't double-page on overlapping incidents.
     */
    private function shouldDeliver(array $payload): bool
    {
        $severity = $payload['severity'] ?? 'info';
        $source = $payload['source'] ?? 'unknown';

        // 'crit' is never rate-limited — pages must always go through.
        if ($severity === 'crit') {
            return true;
        }

        $ttl = (int) config('telegram.rate_limit_seconds', 300);
        $key = "telegram_rate:{$severity}:{$source}";

        // Cache::add returns true on first acquire, false while the key is
        // still held — same semantics as a lockfile.
        return Cache::add($key, 1, $ttl);
    }

    private function resolveChatId(mixed $notifiable): ?string
    {
        if (is_object($notifiable) && method_exists($notifiable, 'routeNotificationFor')) {
            $route = $notifiable->routeNotificationFor('telegram');
            if (! empty($route)) {
                return (string) $route;
            }
        }

        return null;
    }
}
