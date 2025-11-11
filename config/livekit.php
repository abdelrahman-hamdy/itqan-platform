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

    'server_url' => env('LIVEKIT_SERVER_URL', 'wss://test-rn3dlic1.livekit.cloud'),

    // Backend API URL (HTTPS for server-side API calls)
    'api_url' => env('LIVEKIT_API_URL', str_replace('wss://', 'https://', env('LIVEKIT_SERVER_URL', 'https://test-rn3dlic1.livekit.cloud'))),
    'api_key' => env('LIVEKIT_API_KEY', 'APIxdLnkvjeS3PV'),
    'api_secret' => env('LIVEKIT_API_SECRET', 'coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'default_room_settings' => [
        'max_participants' => 50,
        'empty_timeout' => 300, // 5 minutes
        'max_duration' => 7200, // 2 hours
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
];
