<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply CSP to HTML responses
        if ($response->headers->get('Content-Type') &&
            str_contains($response->headers->get('Content-Type'), 'text/html')) {

            // Get the base domain for Vite dev server and subdomains (in development)
            $viteServer = config('app.env') === 'local'
                ? 'https://itqan-platform.test:5173 http://localhost:5173'
                : '';

            // Allow all subdomains in local environment for multi-tenancy
            $subdomains = config('app.env') === 'local'
                ? 'https://*.itqan-platform.test http://*.itqan-platform.test'
                : '';

            // Comprehensive CSP to block external script injection
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: {$viteServer} {$subdomains} https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com https://js.pusher.com",
                "style-src 'self' 'unsafe-inline' {$viteServer} {$subdomains} https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net data:",
                "img-src 'self' data: blob: https:",
                "connect-src 'self' ws: wss: blob: {$viteServer} {$subdomains} https:",
                "media-src 'self' blob: data:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ];

            // Only block mixed content in production
            if (config('app.env') !== 'local') {
                $csp[] = 'block-all-mixed-content';
            }

            // Clean up extra spaces from empty viteServer in production
            $cspString = implode('; ', $csp);
            $cspString = preg_replace('/\s+/', ' ', $cspString);

            $response->headers->set('Content-Security-Policy', $cspString);

            // Additional security headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        return $response;
    }
}
