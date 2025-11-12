<?php

/**
 * Bridge configuration file for Chatify package compatibility
 *
 * This file exists solely to maintain compatibility with the munafio/chatify package
 * which expects config('chatify.*') calls to work.
 *
 * All actual configuration is in config/chat.php
 */

return [
    // Route configuration
    'routes' => [
        'custom' => true, // Use our custom routes from routes/chatify/
        'prefix' => config('chat.routes.prefix', 'chat'),
        'middleware' => config('chat.routes.middleware', ['web','auth']),
        'namespace' => config('chat.routes.namespace', 'App\Http\Controllers\vendor\Chatify'),
    ],

    'api_routes' => [
        'prefix' => config('chat.api_routes.prefix', 'chat/api'),
        'middleware' => config('chat.api_routes.middleware', ['web','auth']),
        'namespace' => config('chat.api_routes.namespace', 'App\Http\Controllers\vendor\Chatify'),
    ],

    // All other settings from chat.php
    'name' => config('chat.name', 'Itqan Chat'),
    'storage_disk_name' => config('chat.storage_disk_name', 'public'),
    'user_avatar' => config('chat.user_avatar'),
    'gravatar' => config('chat.gravatar'),
    'attachments' => config('chat.attachments'),
    'colors' => config('chat.colors'),
    'sounds' => config('chat.sounds'),

    // Reverb settings (for backward compatibility with 'pusher' key)
    'pusher' => config('chat.reverb'),
];
