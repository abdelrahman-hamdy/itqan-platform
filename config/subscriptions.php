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
        'expires_after_hours' => env('SUBSCRIPTION_PENDING_EXPIRY_HOURS', 48),

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

];
