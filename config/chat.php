<?php

return [
    /*
    |-------------------------------------
    | Messenger display name
    |-------------------------------------
    */
    'name' => env('CHAT_NAME', 'Itqan Chat'),

    /*
    |-------------------------------------
    | The disk on which to store added
    | files and derived images by default.
    |-------------------------------------
    */
    'storage_disk_name' => env('CHAT_STORAGE_DISK', 'public'),

    /*
    |-------------------------------------
    | Routes configurations
    |-------------------------------------
    */
    'routes' => [
        'custom' => env('CHAT_CUSTOM_ROUTES', false),
        'prefix' => env('CHAT_ROUTES_PREFIX', 'chat'),
        'middleware' => env('CHAT_ROUTES_MIDDLEWARE', ['web', 'auth']),
        'namespace' => env('CHAT_ROUTES_NAMESPACE', 'App\Http\Controllers\vendor\Chatify'),
    ],
    'api_routes' => [
        'prefix' => env('CHAT_API_ROUTES_PREFIX', 'api/chat'),
        'middleware' => env('CHAT_API_ROUTES_MIDDLEWARE', ['web', 'auth']),
        'namespace' => env('CHAT_API_ROUTES_NAMESPACE', 'App\Http\Controllers\vendor\Chatify'),
    ],

    /*
    |-------------------------------------
    | Laravel Reverb configuration
    |-------------------------------------
    */
    'reverb' => [
        'debug' => env('APP_DEBUG', false),
        'key' => env('REVERB_APP_KEY', 'vil71wafgpp6do1miwn1'),
        'secret' => env('REVERB_APP_SECRET', 'auto0ms5oev2876cfpvt'),
        'app_id' => env('REVERB_APP_ID', 'itqan-platform'),
        'options' => [
            'cluster' => null,
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 8085),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'encrypted' => false,
            'useTLS' => false,
        ],
    ],

    /*
    |-------------------------------------
    | User Avatar
    |-------------------------------------
    */
    'user_avatar' => [
        'folder' => 'users-avatar',
        'default' => 'avatar.png',
    ],

    /*
    |-------------------------------------
    | Gravatar
    |
    | imageset property options:
    | [ 404 | mp | identicon (default) | monsterid | wavatar ]
    |-------------------------------------
    */
    'gravatar' => [
        'enabled' => true,
        'image_size' => 200,
        'imageset' => 'identicon',
    ],

    /*
    |-------------------------------------
    | Attachments
    |-------------------------------------
    */
    'attachments' => [
        'folder' => 'attachments',
        'download_route_name' => 'attachments.download',
        'allowed_images' => (array) ['png', 'jpg', 'jpeg', 'gif', 'webp'],
        'allowed_files' => (array) ['zip', 'rar', 'txt', 'pdf', 'doc', 'docx'],
        'max_upload_size' => env('CHAT_MAX_FILE_SIZE', 150), // MB
    ],

    /*
    |-------------------------------------
    | Messenger's colors
    |-------------------------------------
    */
    'colors' => (array) [
        '#2180f3',
        '#2196F3',
        '#00BCD4',
        '#3F51B5',
        '#673AB7',
        '#4CAF50',
        '#FFC107',
        '#FF9800',
        '#ff2522',
        '#9C27B0',
    ],

    /*
    |-------------------------------------
    | Sounds
    | You can enable/disable the sounds and
    | change sound's name/path placed at
    | `public/` directory of your app.
    |
    |-------------------------------------
    */
    'sounds' => [
        'enabled' => true,
        'public_path' => 'sounds/chat',
        'new_message' => 'new-message-sound.mp3',
    ],

    /*
    |-------------------------------------
    | Pagination
    |-------------------------------------
    */
    'pagination' => [
        'per_page' => 30,
        'contacts_per_page' => 50,
    ],

    /*
    |-------------------------------------
    | Cache settings
    |-------------------------------------
    */
    'cache' => [
        'enabled' => env('CHAT_CACHE_ENABLED', true),
        'ttl' => env('CHAT_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'chat:',
    ],
];
