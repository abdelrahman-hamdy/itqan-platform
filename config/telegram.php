<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Alert Channel
    |--------------------------------------------------------------------------
    |
    | One-bot, one-chat oncall pager. Mirrors the shell-side dispatcher at
    | /usr/local/bin/itqan-alert (LiveKit VPS + app server). Token + chat_id
    | are intentionally shared across all producers so every alert lands in
    | the same Telegram inbox.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'chat_id' => env('TELEGRAM_CHAT_ID'),

    'enabled' => env('TELEGRAM_ALERTS_ENABLED', false),

    'queue' => env('TELEGRAM_ALERTS_QUEUE', 'notifications'),

    'rate_limit_seconds' => env('TELEGRAM_ALERTS_RATE_LIMIT', 300),

    'request_timeout' => env('TELEGRAM_ALERTS_TIMEOUT', 8),

    'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org'),

    'renewal_failure_threshold' => env('TELEGRAM_RENEWAL_FAILURE_THRESHOLD', 5),

    'ssl_cert_path' => env('SSL_CERT_PATH', '/etc/letsencrypt/live/itqanway.com/cert.pem'),

];
