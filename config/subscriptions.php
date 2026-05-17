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

        // Bug #9 gateway-retry envelope: when a fresh sub-creation attempt finds
        // a CANCELLED sibling for the same combination within this many minutes,
        // the service un-cancels and reuses that row instead of minting a new
        // one. The ghost-sub failure mode was a 1-minute spread between expire
        // and retry; 60m is a generous safety buffer.
        'retry_window_minutes' => env('SUBSCRIPTION_RETRY_WINDOW_MINUTES', 60),
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

    /*
    |--------------------------------------------------------------------------
    | Bug-Fix Feature Flags
    |--------------------------------------------------------------------------
    |
    | Each bug fix from docs/subscription-bugs-found.md is gated by its own
    | flag so a single change can be killed in prod via `.env` without
    | redeploy. Default-on; flip to false to disable a specific fix.
    |
    */

    'fixes' => [
        'bug_1_supervisor_pause_resume' => env('BUG_1_ENABLED', true),
        'bug_2_quran_teacher_lookup' => env('BUG_2_ENABLED', true),
        'bug_3_academic_total_price_skip' => env('BUG_3_ENABLED', true),
        'bug_5_earnings_morph_alias' => env('BUG_5_ENABLED', true),
        'bug_9_gateway_retry_reuse' => env('BUG_9_ENABLED', true),
        'bug_11_activate_cancelled_guard' => env('BUG_11_ENABLED', true),
        'bug_12_pending_filter_status' => env('BUG_12_ENABLED', true),
        'bug_13_queued_cycle_paid_only' => env('BUG_13_ENABLED', true),
        'bug_14_cleanup_payment_status_column' => env('BUG_14_ENABLED', true),
        'bug_16_resubscribe_starts_at_reset' => env('BUG_16_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | v2 Refactor — feature flags + safety limits
    |--------------------------------------------------------------------------
    |
    | Phase A.3 of the subscription v2 refactor. See:
    |   - docs/subscription-recovery-plan.md  (phases + exit criteria)
    |   - docs/subscription-invariants.md     (numbered invariants)
    |
    | All values are safe defaults; operators flip the env vars below to
    | progress through phases.
    |
    */

    // Master kill-switch for the v2 mutators (SubscriptionLifecycle,
    // SubscriptionConsumption, SubscriptionPayment, SubscriptionPricing).
    // Phase A keeps this false; flipped on in Phase D as part of
    // controlled-production observation.
    'v2_enabled' => env('SUBSCRIPTIONS_V2_ENABLED', false),

    // Tier 2 dual-execution: when true, every webhook + supervisor-cash
    // payment runs the v2 SubscriptionLifecycle / SubscriptionPayment path
    // IN ADDITION to the legacy activateFromPayment path. v2 writes a
    // SubscriptionAuditLog row + a divergence summary; any exception from
    // the v2 path is swallowed and audit-logged so it can NEVER break the
    // legacy/canonical write. Flip on in staging for a week of zero
    // divergence before turning on the canonical flag below.
    'v2_payment_dual' => env('SUBSCRIPTIONS_V2_PAYMENT_DUAL', false),

    // Once dual execution has proven zero divergence, flip this to true to
    // make v2 the canonical writer (legacy then runs as the shadow). Until
    // then, legacy stays canonical. Phase A.5 default: false.
    'v2_payment_canonical' => env('SUBSCRIPTIONS_V2_PAYMENT_CANONICAL', false),

    // When true, SubscriptionRowGuard throws UnreconciledSubscriptionWrite
    // on any direct write to derived subscription columns (payment_status,
    // sessions_used, sessions_remaining, total_sessions, starts_at,
    // ends_at) outside SubscriptionReconciler::sync.
    // When false (Phase A default), the guard only logs warnings so the
    // migration to v2 mutators can proceed without breaking legacy code.
    'row_guard_enforce' => env('SUBSCRIPTIONS_ROW_GUARD_ENFORCE', false),

    // Upper bound on $graceDays passed to Subscription::extend(). Anything
    // higher requires an admin-override actor + audit entry. Default 14.
    // See INV-F2 and decision table 3.4.
    'max_grace_days' => env('SUBSCRIPTIONS_MAX_GRACE_DAYS', 14),

    // Maximum pause window before cron auto-expires the subscription per
    // decision table 3.5. Default 30.
    'max_pause_days' => env('SUBSCRIPTIONS_MAX_PAUSE_DAYS', 30),

    // How long an interactive (web/API) mutator blocks waiting for the
    // per-subscription advisory lock before raising
    // SubscriptionLockTimeout. INV-C1.
    'lock_wait_seconds' => env('SUBSCRIPTIONS_LOCK_WAIT_SECONDS', 5),

    // How long a cron-path mutator blocks waiting for the per-subscription
    // lock before skipping the sub and audit-logging cron_skipped_locked.
    // INV-C3 caps this at ≤ 2 seconds.
    'cron_lock_wait_seconds' => env('SUBSCRIPTIONS_CRON_LOCK_WAIT_SECONDS', 2),

];
