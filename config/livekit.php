<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LiveKit Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for LiveKit video conferencing.
    |
    */

    // Self-hosted LiveKit server
    // SECURITY: No default URLs - must be explicitly configured per environment
    'server_url' => env('LIVEKIT_SERVER_URL'),
    'api_url' => env('LIVEKIT_API_URL'),
    'api_key' => env('LIVEKIT_API_KEY'),
    'api_secret' => env('LIVEKIT_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'default_room_settings' => [
        'max_participants' => 50,
        'empty_timeout' => 300, // 5 minutes (seconds)
        'max_duration' => 7200, // 2 hours (seconds)
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Meeting Settings
    |--------------------------------------------------------------------------
    */

    'session_settings' => [
        // Minutes to add to session duration for empty timeout calculation
        'timeout_buffer_minutes' => 30,
        // Minutes to add to session duration as overtime buffer
        'overtime_buffer_minutes' => 60,
        // Default session duration if not specified (minutes)
        'default_duration_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Settings
    |--------------------------------------------------------------------------
    */

    'token_settings' => [
        'ttl' => 3600, // 1 hour
        'identity_prefix' => 'itqan_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Recording Settings
    |--------------------------------------------------------------------------
    |
    | Recordings are stored on the LiveKit server and served via nginx.
    | The recordings_base_url should point to the nginx location serving
    | the /recordings directory on the LiveKit server.
    |
    */

    'recordings' => [
        // Base URL where recordings are accessible (served by nginx on LiveKit server)
        // SECURITY: No default URL - must be explicitly configured per environment
        'base_url' => env('LIVEKIT_RECORDINGS_URL'),

        // Storage path on the LiveKit server (used by Egress)
        'storage_path' => env('LIVEKIT_RECORDINGS_PATH', '/recordings'),

        // Whether to proxy recordings through Laravel or redirect directly
        // 'proxy' = Laravel fetches and streams (more control, uses Laravel bandwidth)
        // 'redirect' = Redirect to direct URL (faster, uses LiveKit server bandwidth)
        'access_mode' => env('LIVEKIT_RECORDINGS_ACCESS_MODE', 'redirect'),
    ],
];
