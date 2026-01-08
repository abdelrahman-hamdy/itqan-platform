<?php

/**
 * Sentry Dashboard Configuration
 *
 * These credentials are used for the admin dashboard widget
 * to fetch error statistics from Sentry's API.
 *
 * Get an auth token from: https://sentry.io/settings/account/api/auth-tokens/
 * The token needs the following scopes: project:read, event:read
 */
return [
    // Your Sentry organization slug (from your Sentry URL)
    'organization_slug' => env('SENTRY_ORG_SLUG'),

    // Your Sentry project slug (from your Sentry URL)
    'project_slug' => env('SENTRY_PROJECT_SLUG'),

    // API auth token with project:read and event:read scopes
    'auth_token' => env('SENTRY_AUTH_TOKEN'),
];
