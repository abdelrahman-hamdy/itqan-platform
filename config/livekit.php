<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LiveKit Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your LiveKit server connection details. You can either use
    | LiveKit Cloud (managed service) or self-host your own LiveKit server.
    |
    */

    'server_url' => env('LIVEKIT_SERVER_URL', 'wss://test-rn3dlic1.livekit.cloud'),
    'api_key' => env('LIVEKIT_API_KEY', 'APIxdLnkvjeS3PV'),
    'api_secret' => env('LIVEKIT_API_SECRET', 'coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJ'),

    /*
    |--------------------------------------------------------------------------
    | Meeting Room Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for newly created meeting rooms.
    |
    */

    'room_defaults' => [
        'max_participants' => env('LIVEKIT_MAX_PARTICIPANTS', 50),
        'empty_timeout' => env('LIVEKIT_EMPTY_TIMEOUT', 300), // 5 minutes
        'max_duration' => env('LIVEKIT_MAX_DURATION', 7200), // 2 hours
        'enable_recording' => env('LIVEKIT_ENABLE_RECORDING', false),
        'auto_record' => env('LIVEKIT_AUTO_RECORD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Token Settings
    |--------------------------------------------------------------------------
    |
    | Configure access token generation and validation settings.
    |
    */

    'token' => [
        'ttl' => env('LIVEKIT_TOKEN_TTL', 10800), // 3 hours in seconds
        'not_before' => env('LIVEKIT_TOKEN_NBF', 0), // Token valid from (seconds from now)
    ],

    /*
    |--------------------------------------------------------------------------
    | Recording Configuration
    |--------------------------------------------------------------------------
    |
    | Configure recording storage and processing options.
    |
    */

    'recording' => [
        'enabled' => env('LIVEKIT_RECORDING_ENABLED', false),
        'storage_type' => env('LIVEKIT_RECORDING_STORAGE', 'local'), // local, s3, gcs
        
        // S3 Configuration (if using S3 storage)
        's3_bucket' => env('LIVEKIT_RECORDING_S3_BUCKET'),
        's3_region' => env('LIVEKIT_RECORDING_S3_REGION', 'us-east-1'),
        's3_access_key' => env('LIVEKIT_RECORDING_S3_ACCESS_KEY'),
        's3_secret_key' => env('LIVEKIT_RECORDING_S3_SECRET_KEY'),
        
        // Local Storage Configuration
        'local_path' => env('LIVEKIT_RECORDING_LOCAL_PATH', storage_path('app/recordings')),
        
        // Recording Quality Settings
        'video_quality' => env('LIVEKIT_RECORDING_VIDEO_QUALITY', 'high'), // low, medium, high
        'audio_quality' => env('LIVEKIT_RECORDING_AUDIO_QUALITY', 'high'),
        'layout' => env('LIVEKIT_RECORDING_LAYOUT', 'grid'), // grid, speaker, custom
        
        // Auto-cleanup settings
        'auto_cleanup' => env('LIVEKIT_RECORDING_AUTO_CLEANUP', false),
        'cleanup_after_days' => env('LIVEKIT_RECORDING_CLEANUP_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhooks to receive real-time events from LiveKit server.
    |
    */

    'webhooks' => [
        'enabled' => env('LIVEKIT_WEBHOOKS_ENABLED', true),
        'endpoint' => env('LIVEKIT_WEBHOOK_ENDPOINT', '/webhooks/livekit'),
        'secret' => env('LIVEKIT_WEBHOOK_SECRET'),
        
        // Events to listen for
        'events' => [
            'room_started',
            'room_finished', 
            'participant_joined',
            'participant_left',
            'recording_started',
            'recording_finished',
            'egress_started',
            'egress_ended',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific LiveKit features based on your needs.
    |
    */

    'features' => [
        'adaptive_stream' => env('LIVEKIT_ADAPTIVE_STREAM', true),
        'dynacast' => env('LIVEKIT_DYNACAST', true),
        'simulcast' => env('LIVEKIT_SIMULCAST', true),
        'end_to_end_encryption' => env('LIVEKIT_E2E_ENCRYPTION', false),
        'noise_cancellation' => env('LIVEKIT_NOISE_CANCELLATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Customization
    |--------------------------------------------------------------------------
    |
    | Customize the meeting room interface and branding.
    |
    */

    'ui' => [
        'theme' => env('LIVEKIT_UI_THEME', 'light'), // light, dark, custom
        'primary_color' => env('LIVEKIT_UI_PRIMARY_COLOR', '#3B82F6'),
        'logo_url' => env('LIVEKIT_UI_LOGO_URL'),
        'favicon_url' => env('LIVEKIT_UI_FAVICON_URL'),
        'custom_css' => env('LIVEKIT_UI_CUSTOM_CSS'),
        
        // Meeting controls
        'show_participant_count' => env('LIVEKIT_UI_SHOW_PARTICIPANT_COUNT', true),
        'enable_chat' => env('LIVEKIT_UI_ENABLE_CHAT', true),
        'enable_screen_share' => env('LIVEKIT_UI_ENABLE_SCREEN_SHARE', true),
        'enable_recording_indicator' => env('LIVEKIT_UI_RECORDING_INDICATOR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings for optimal meeting experience.
    |
    */

    'performance' => [
        'video_resolution' => env('LIVEKIT_VIDEO_RESOLUTION', '720p'), // 720p, 1080p, 480p
        'video_fps' => env('LIVEKIT_VIDEO_FPS', 30),
        'audio_bitrate' => env('LIVEKIT_AUDIO_BITRATE', 64), // kbps
        'video_bitrate' => env('LIVEKIT_VIDEO_BITRATE', 1500), // kbps
        
        // Network optimization
        'ice_servers' => [
            // You can add custom STUN/TURN servers here
            ['urls' => 'stun:stun.l.google.com:19302'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */

    'development' => [
        'debug_mode' => env('LIVEKIT_DEBUG', false),
        'log_level' => env('LIVEKIT_LOG_LEVEL', 'info'), // debug, info, warn, error
        'mock_recording' => env('LIVEKIT_MOCK_RECORDING', false),
        'test_room_prefix' => env('LIVEKIT_TEST_PREFIX', 'test-'),
    ],

];
