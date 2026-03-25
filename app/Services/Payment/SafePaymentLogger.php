<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

/**
 * Safe wrapper around the 'payments' log channel.
 *
 * Logging failures must NEVER crash payment business logic.
 * If the payments channel fails (e.g. file permission issues),
 * this falls back to the default log channel silently.
 */
class SafePaymentLogger
{
    public static function info(string $message, array $context = []): void
    {
        static::log('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        static::log('warning', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        static::log('debug', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        static::log('critical', $message, $context);
    }

    private static function log(string $level, string $message, array $context): void
    {
        try {
            Log::channel('payments')->{$level}($message, $context);
        } catch (\Throwable) {
            // Fall back to default logger — payment must never crash due to logging
            Log::{$level}("[payments] {$message}", $context);
        }
    }
}
