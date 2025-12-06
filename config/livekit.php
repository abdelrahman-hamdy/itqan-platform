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
    'server_url' => env('LIVEKIT_SERVER_URL', 'wss://conference.itqanway.com'),
    'api_url' => env('LIVEKIT_API_URL', 'https://conference.itqanway.com'),
    'api_key' => env('LIVEKIT_API_KEY'),
    'api_secret' => env('LIVEKIT_API_SECRET'),

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
