<?php

use App\Notifications\Channels\TelegramChannel;
use App\Notifications\CriticalAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

if (! function_exists('alert_telegram')) {
    /**
     * @param  string  $severity  'crit' | 'medium' | 'info'
     * @param  string  $source  short tag (e.g. 'payment-storm', 'horizon')
     * @param  string  $message  human-readable body
     */
    function alert_telegram(string $severity, string $source, string $message): void
    {
        if (! config('telegram.enabled')) {
            return;
        }

        $chatId = config('telegram.chat_id');

        if (empty($chatId)) {
            Log::warning('alert_telegram: chat_id missing — dropping alert', [
                'severity' => $severity,
                'source' => $source,
            ]);

            return;
        }

        try {
            Notification::route(TelegramChannel::class, (string) $chatId)
                ->notify(new CriticalAlertNotification($severity, $source, $message));
        } catch (\Throwable $e) {
            Log::warning('alert_telegram: dispatch failed', [
                'severity' => $severity,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
