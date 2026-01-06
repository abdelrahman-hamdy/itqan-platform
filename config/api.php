<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the API endpoints.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default pagination settings used across API endpoints.
    |
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Parent Sessions
    |--------------------------------------------------------------------------
    |
    | Limit for session queries when fetching sessions for parent's children.
    | This limit applies per session type (quran, academic, interactive).
    |
    */

    'parent_sessions_limit' => env('API_PARENT_SESSIONS_LIMIT', 50),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for API endpoints.
    |
    */

    'rate_limiting' => [
        'enabled' => env('API_RATE_LIMITING_ENABLED', true),
        'default_limit' => env('API_RATE_LIMIT', 60),
        'per_minutes' => env('API_RATE_LIMIT_MINUTES', 1),
    ],

];
