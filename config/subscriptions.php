<?php

/**
 * Subscription Configuration
 *
 * Configuration settings for subscription management including
 * pending expiry, duplicate handling, and cleanup settings.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Pending Subscription Settings
    |--------------------------------------------------------------------------
    |
    | Settings for pending subscriptions that are awaiting payment.
    |
    */

    'pending' => [
        // Hours after which a pending subscription expires if not paid
        'expires_after_hours' => env('SUBSCRIPTION_PENDING_EXPIRY_HOURS', 24),

        // Extra buffer time (in minutes) for slow webhooks before considering expired
        'grace_period_minutes' => env('SUBSCRIPTION_GRACE_PERIOD_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate Subscription Handling
    |--------------------------------------------------------------------------
    |
    | Settings for handling duplicate pending subscriptions.
    |
    */

    'duplicates' => [
        // Maximum number of pending subscriptions allowed per teacher/package combination
        'max_pending_per_combination' => 1,

        // Whether to automatically cancel old pending subscriptions when a new one is created
        'auto_cancel_old_pending' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the cleanup artisan command.
    |
    */

    'cleanup' => [
        // Number of subscriptions to process per batch (to avoid memory issues)
        'batch_size' => 100,

        // Whether to log each deleted/cancelled subscription
        'log_deletions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cancellation Reasons
    |--------------------------------------------------------------------------
    |
    | Default cancellation reasons in Arabic.
    |
    */

    'cancellation_reasons' => [
        'expired' => 'انتهت صلاحية طلب الاشتراك - لم يتم الدفع خلال المهلة المحددة',
        'duplicate' => 'تم إلغاء الاشتراك المعلق السابق بسبب إنشاء طلب جديد',
        'admin' => 'ألغي بواسطة الإدارة',
        'payment_failed' => 'فشل في معالجة الدفع',
    ],

    // Legacy pause reason used before the sessions_exhausted metadata flag was introduced.
    // Used only for backwards-compatible detection in returnSession() and data migrations.
    'legacy_sessions_exhausted_pause_reason' => 'انتهت الجلسات المتاحة - في انتظار التجديد',

    /*
    |--------------------------------------------------------------------------
    | Grace Period Settings
    |--------------------------------------------------------------------------
    |
    | When admin extends a subscription, the default grace period length
    | (in days) suggested in the UI. Student keeps scheduling during grace.
    | If `auto_pause_on_lapse` is true, `subscriptions:expire-active` pauses
    | the subscription when grace lapses without payment.
    |
    */

    'grace' => [
        'default_days' => env('SUBSCRIPTION_GRACE_DEFAULT_DAYS', 14),
        'auto_pause_on_lapse' => env('SUBSCRIPTION_GRACE_AUTO_PAUSE', true),

        // Max number of extension audit entries retained in subscription.metadata.
        // Older entries are pruned on each new extend() call to prevent unbounded
        // JSON growth (each entry is ~150 bytes; 50 cap ≈ 7.5KB worst case).
        'extensions_log_cap' => env('SUBSCRIPTION_EXTENSIONS_LOG_CAP', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cycles Settings
    |--------------------------------------------------------------------------
    |
    | Subscription renewal advances the subscription into a new cycle row in
    | `subscription_cycles`. `max_queued_per_thread` enforces "at most one
    | queued future cycle per subscription" so early-renewal doesn't stack.
    |
    */

    'cycles' => [
        'max_queued_per_thread' => 1,
    ],

];
