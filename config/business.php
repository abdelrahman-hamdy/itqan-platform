<?php

/**
 * Business Rules Configuration
 *
 * Centralizes hardcoded business values that were previously scattered
 * across services, controllers, and traits.
 *
 * USAGE:
 * - config('business.attendance.grace_period_minutes')
 * - config('business.sessions.buffer_time_minutes')
 * - config('business.pagination.notifications')
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    */
    'attendance' => [
        // Grace period in minutes before marking as late
        'grace_period_minutes' => env('ATTENDANCE_GRACE_PERIOD', 15),

        // Threshold percentage for attendance calculation (>= this = attended)
        'threshold_percent' => env('ATTENDANCE_THRESHOLD_PERCENT', 80),

        // Minimum percentage to be considered present (< this = absent)
        'minimum_presence_percent' => env('ATTENDANCE_MIN_PRESENCE', 50),

        // Excellent attendance percentage (late arrival but still counts as late not absent)
        'excellent_percent' => env('ATTENDANCE_EXCELLENT_PERCENT', 95),

        // Minimum percentage to count as left (vs absent) for on-time joins
        'left_threshold_percent' => env('ATTENDANCE_LEFT_THRESHOLD', 30),

        // Post-session grace period in minutes (overtime allowance after scheduled end)
        'post_session_grace_minutes' => env('ATTENDANCE_POST_SESSION_GRACE', 30),

        // Calculation delay in minutes after session ends
        'calculation_delay_minutes' => env('ATTENDANCE_CALCULATION_DELAY', 5),

        // Reconnection threshold in seconds (merge cycles if rejoining within this time)
        'reconnection_threshold_seconds' => env('ATTENDANCE_RECONNECTION_THRESHOLD', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'sessions' => [
        // Buffer time after session end before auto-completion
        'buffer_time_minutes' => env('SESSION_BUFFER_TIME', 5),

        // Transition delay in seconds
        'transition_delay_seconds' => env('SESSION_TRANSITION_DELAY', 60),

        // Default session duration in minutes
        'default_duration_minutes' => env('SESSION_DEFAULT_DURATION', 60),

        // Maximum sessions per batch scheduling
        'max_batch_sessions' => env('SESSION_MAX_BATCH', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meeting Configuration
    |--------------------------------------------------------------------------
    */
    'meetings' => [
        // Buffer time in minutes before meeting creation
        'creation_buffer_minutes' => env('MEETING_CREATION_BUFFER', 30),

        // Preparation time in minutes before session
        'preparation_minutes' => env('MEETING_PREPARATION_TIME', 15),

        // Maximum meeting duration in seconds (3 hours)
        'max_duration_seconds' => env('MEETING_MAX_DURATION', 10800),

        // Maximum participants per meeting
        'max_participants' => env('MEETING_MAX_PARTICIPANTS', 50),

        // Connection timeout in seconds
        'connection_timeout_seconds' => env('MEETING_CONNECTION_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Configuration (in seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Statistics cache duration
        'statistics_ttl' => env('CACHE_STATISTICS_TTL', 600),

        // Calendar cache duration
        'calendar_ttl' => env('CACHE_CALENDAR_TTL', 300),

        // Dashboard widgets cache duration
        'dashboard_ttl' => env('CACHE_DASHBOARD_TTL', 1800),

        // Academy context cache duration
        'academy_context_ttl' => env('CACHE_ACADEMY_CONTEXT_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'notifications' => env('PAGINATION_NOTIFICATIONS', 15),
        'sessions' => env('PAGINATION_SESSIONS', 20),
        'students' => env('PAGINATION_STUDENTS', 50),
        'teachers' => env('PAGINATION_TEACHERS', 25),
        'payments' => env('PAGINATION_PAYMENTS', 20),
        'default' => env('PAGINATION_DEFAULT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress and Quiz Configuration
    |--------------------------------------------------------------------------
    */
    'progress' => [
        // Minimum percentage for progress calculation
        'minimum_percent' => env('PROGRESS_MINIMUM_PERCENT', 50),
    ],

    'quiz' => [
        // Passing score percentage
        'passing_score' => env('QUIZ_PASSING_SCORE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Configuration
    |--------------------------------------------------------------------------
    */
    'subscriptions' => [
        // Days before expiry to send renewal reminder
        'renewal_reminder_days' => env('SUBSCRIPTION_RENEWAL_REMINDER_DAYS', 7),

        // Default billing cycle for new subscriptions
        'default_billing_cycle' => env('SUBSCRIPTION_DEFAULT_BILLING_CYCLE', 'monthly'),

        // Trial period duration in days
        'default_trial_days' => env('SUBSCRIPTION_TRIAL_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */
    'tokens' => [
        // LiveKit token TTL in seconds (3 hours)
        'livekit_ttl' => env('LIVEKIT_TOKEN_TTL', 10800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Date and Time Configuration
    |--------------------------------------------------------------------------
    */
    'datetime' => [
        // Default display date format
        'date_format' => env('DISPLAY_DATE_FORMAT', 'Y/m/d'),

        // Default display time format (12-hour)
        'time_format' => env('DISPLAY_TIME_FORMAT', 'g:i A'),

        // Default datetime format
        'datetime_format' => env('DISPLAY_DATETIME_FORMAT', 'Y/m/d - g:i A'),

        // Storage date format (ISO 8601)
        'storage_date_format' => 'Y-m-d',

        // Storage datetime format
        'storage_datetime_format' => 'Y-m-d H:i:s',
    ],
];
