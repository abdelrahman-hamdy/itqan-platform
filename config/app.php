<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Domain
    |--------------------------------------------------------------------------
    |
    | This is the base domain for the application. Used for multi-tenancy
    | and subdomain routing.
    |
    */

    'domain' => env('APP_DOMAIN', 'itqan-platform.test'),

    /*
    |--------------------------------------------------------------------------
    | Default Academy Subdomain
    |--------------------------------------------------------------------------
    |
    | The default academy subdomain used as a fallback when no subdomain
    | is resolved from the request. This is the primary academy tenant.
    |
    */

    'default_academy_subdomain' => env('DEFAULT_ACADEMY_SUBDOMAIN', 'itqan-academy'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Email Addresses
    |--------------------------------------------------------------------------
    |
    | Default email addresses used as fallbacks in payment gateways and
    | other services when no user-specific email is available.
    |
    */

    'fallback_email' => env('APP_FALLBACK_EMAIL', 'noreply@itqanway.com'),

    /*
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    |
    | Public-facing contact details displayed in footers, contact pages,
    | and terms pages as fallbacks when no platform/academy settings exist.
    |
    */

    'contact_email' => env('APP_CONTACT_EMAIL', 'info@itqanway.com'),
    'contact_phone' => env('APP_CONTACT_PHONE', '+966 50 123 4567'),
    'admin_email' => env('APP_ADMIN_EMAIL', 'admin@itqanway.com'),
    'contact_address' => env('APP_CONTACT_ADDRESS', 'الرياض، المملكة العربية السعودية'),

    /*
    |--------------------------------------------------------------------------
    | ICS Calendar Domain
    |--------------------------------------------------------------------------
    |
    | The domain used for UID generation in ICS calendar exports.
    | Format: eventId@domain
    |
    */

    'ics_domain' => env('APP_ICS_DOMAIN', 'itqanway.com'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We recommend
    | keeping this as UTC and handling timezone conversion at the display layer.
    |
    | IMPORTANT: Per-academy timezone is stored in academies.timezone field.
    | Use AcademyContextService::getTimezone() and helper functions for
    | timezone-aware operations. Do NOT rely on this config for business logic.
    |
    | Helper functions (auto-loaded from app/Helpers/TimeHelper.php):
    | - getAcademyTimezone() - Get current academy's timezone
    | - nowInAcademyTimezone() - Current time in academy timezone
    | - toAcademyTimezone($time) - Convert to academy timezone
    | - formatTimeArabic($time) - Format in Arabic with timezone conversion
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'ar'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ar'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
