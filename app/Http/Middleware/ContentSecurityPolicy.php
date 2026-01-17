<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy Middleware
 *
 * Provides defense-in-depth against XSS and injection attacks.
 * Configuration is environment-aware with stricter settings in production.
 *
 * Security considerations:
 * - 'unsafe-eval' is required for LiveKit SDK in development
 * - 'unsafe-inline' is required for Livewire and Alpine.js
 * - WebSocket (ws:/wss:) is required for real-time features
 * - blob: is required for LiveKit media handling
 */
class ContentSecurityPolicy
{
    /**
     * Trusted CDN domains for scripts and styles
     */
    private const TRUSTED_SCRIPT_CDNS = [
        'https://cdn.jsdelivr.net',
        'https://unpkg.com',
        'https://js.pusher.com',
    ];

    /**
     * Trusted CDN domains for styles
     */
    private const TRUSTED_STYLE_CDNS = [
        'https://fonts.googleapis.com',
        'https://fonts.bunny.net',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
    ];

    /**
     * Trusted domains for fonts
     */
    private const TRUSTED_FONT_SOURCES = [
        'https://fonts.gstatic.com',
        'https://fonts.bunny.net',
        'https://cdnjs.cloudflare.com',
        'https://cdn.jsdelivr.net',
    ];

    /**
     * Trusted domains for images (whitelisted CDNs)
     * Note: 'https:' is kept for user-uploaded content on various storage providers
     */
    private const TRUSTED_IMAGE_SOURCES = [
        'https://livekit.io',
        'https://cdn.jsdelivr.net',
    ];

    /**
     * Trusted domains for WebSocket/API connections
     */
    private const TRUSTED_CONNECT_SOURCES = [
        'https://soketi.itqan-academy.com',
        'https://api.livekit.io',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply CSP to HTML responses
        if ($response->headers->get('Content-Type') &&
            str_contains($response->headers->get('Content-Type'), 'text/html')) {

            // Allow iframe embedding for log-viewer (embedded in Filament admin)
            $isLogViewer = str_starts_with($request->path(), 'log-viewer');

            $csp = $this->buildCspDirectives($isLogViewer);
            $response->headers->set('Content-Security-Policy', $csp);

            // Additional security headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', $isLogViewer ? 'SAMEORIGIN' : 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Permissions Policy to disable unnecessary browser features
            $response->headers->set('Permissions-Policy', 'geolocation=(), payment=(), usb=()');
        }

        return $response;
    }

    /**
     * Build CSP directives based on environment
     */
    private function buildCspDirectives(bool $allowIframe = false): string
    {
        $isLocal = config('app.env') === 'local';

        // Development-only sources
        $viteServer = $isLocal
            ? 'https://itqan-platform.test:5173 http://localhost:5173'
            : '';
        $subdomains = $isLocal
            ? 'https://*.itqan-platform.test http://*.itqan-platform.test'
            : '';

        // Script sources - 'unsafe-eval' required for Alpine.js and LiveKit
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' blob: data: {$viteServer} {$subdomains} "
            .implode(' ', self::TRUSTED_SCRIPT_CDNS);

        // Style sources
        $styleSrc = "'self' 'unsafe-inline' {$viteServer} {$subdomains} "
            .implode(' ', self::TRUSTED_STYLE_CDNS);

        // Font sources
        $fontSrc = "'self' data: ".implode(' ', self::TRUSTED_FONT_SOURCES);

        // Image sources - allow https: for user content but log specific CDNs
        $imgSrc = "'self' data: blob: ".implode(' ', self::TRUSTED_IMAGE_SOURCES);
        // Allow https: for user-uploaded content from various providers
        $imgSrc .= ' https:';

        // Connect sources - WebSocket for Reverb/Pusher, HTTPS for APIs
        $connectSrc = "'self' ws: wss: blob: {$viteServer} {$subdomains} "
            .implode(' ', self::TRUSTED_CONNECT_SOURCES);
        // Allow https: for API calls (payment gateways, LiveKit, etc.)
        $connectSrc .= ' https:';

        // Frame ancestors - allow same-origin for log-viewer iframe
        $frameAncestors = $allowIframe ? "'self'" : "'none'";

        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src {$fontSrc}",
            "img-src {$imgSrc}",
            "connect-src {$connectSrc}",
            "media-src 'self' blob: data: https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' {$subdomains}",
            "frame-ancestors {$frameAncestors}",
        ];

        // Production-only: block mixed content
        if (! $isLocal) {
            $directives[] = 'block-all-mixed-content';
            $directives[] = 'upgrade-insecure-requests';
        }

        // Clean up extra spaces and return
        $cspString = implode('; ', $directives);

        return preg_replace('/\s+/', ' ', $cspString);
    }
}
