<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Avatars (Default Avatar Generator)
    |--------------------------------------------------------------------------
    |
    | Used to generate default avatar images from user initials.
    | @see https://ui-avatars.com/
    |
    */

    'ui_avatars' => [
        'base_url' => env('UI_AVATARS_BASE_URL', 'https://ui-avatars.com/api/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentry API
    |--------------------------------------------------------------------------
    |
    | Base URLs for Sentry dashboard and API integration.
    | Used by the admin dashboard widget to fetch error statistics.
    |
    */

    'sentry' => [
        'base_url' => env('SENTRY_BASE_URL', 'https://sentry.io'),
    ],

];
