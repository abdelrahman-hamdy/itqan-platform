<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        env('APP_URL', 'https://itqan-platform.test'),
        // Mobile app origins - add your mobile app URLs here
        'capacitor://localhost',
        'ionic://localhost',
        'http://localhost',
        'http://localhost:8080',
        'http://localhost:8100',
        // Production mobile app URLs (configure in .env)
        env('MOBILE_APP_URL') ?: null,
    ], fn ($v) => ! empty($v)),

    'allowed_origins_patterns' => [
        // Allow all subdomains of the main domain for multi-tenancy
        '#^https?://.*\.itqan-platform\.test$#',
        '#^https://.*\.itqanway\.com$#',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-Academy-Subdomain',  // Custom header for academy resolution
        'X-Request-ID',         // Request tracking
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-Request-ID',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 3600,  // Cache preflight requests for 1 hour

    'supports_credentials' => true,

];
